<?php
declare(strict_types=1);

namespace Modules\Promotions;

use Core\Auth;
use Core\Request;
use Core\Response;
use PDO;

final class PromotionController
{
    public function __construct(private PDO $pdo, private Auth $auth) {}

    public function publicList(): never
    {
        $stmt = $this->pdo->query("SELECT code,title,description,discount_type,discount_value,ends_at
            FROM promotions
            WHERE is_active=1 AND starts_at<=NOW() AND ends_at>=NOW()
              AND (max_uses IS NULL OR uses_count<max_uses)
            ORDER BY ends_at ASC");
        Response::success($stmt->fetchAll(), 'Promotions disponibles.');
    }

    public function adminList(): never
    {
        $this->auth->requireRoles(['admin']);
        $stmt = $this->pdo->query("SELECT id,code,title,description,discount_type,discount_value,starts_at,ends_at,max_uses,uses_count,is_active,created_at
            FROM promotions ORDER BY created_at DESC");
        Response::success($stmt->fetchAll(), 'Promotions.');
    }

    public function create(): never
    {
        $user = $this->auth->requireRoles(['admin']);
        $d = Request::json();
        $code = strtoupper(trim((string)($d['code'] ?? '')));
        $title = trim((string)($d['title'] ?? ''));
        $type = (string)($d['discount_type'] ?? '');
        $value = $d['discount_value'] ?? null;
        $start = $this->dateValue($d['starts_at'] ?? '');
        $end = $this->dateValue($d['ends_at'] ?? '');
        $maxUses = ($d['max_uses'] ?? '') === '' || ($d['max_uses'] ?? null) === null ? null : (int)$d['max_uses'];

        if (!preg_match('/^[A-Z0-9_-]{3,40}$/', $code)) Response::error('Le code doit contenir 3 à 40 caractères (lettres, chiffres, tiret).', 422);
        if ($title === '' || !in_array($type, ['percent','fixed'], true) || !is_numeric($value) || (float)$value <= 0) Response::error('Informations de promotion invalides.', 422);
        if ($type === 'percent' && (float)$value > 100) Response::error('Une remise en pourcentage ne peut pas dépasser 100%.', 422);
        if (!$start || !$end || $end <= $start) Response::error('Les dates de début et de fin sont invalides.', 422);
        if ($maxUses !== null && $maxUses < 1) Response::error('Le nombre maximum d’utilisations doit être positif.', 422);

        try {
            $stmt = $this->pdo->prepare('INSERT INTO promotions (code,title,description,discount_type,discount_value,starts_at,ends_at,max_uses,created_by) VALUES (:code,:title,:description,:type,:value,:start,:end,:max_uses,:user)');
            $stmt->execute([
                'code'=>$code, 'title'=>$title, 'description'=>trim((string)($d['description'] ?? '')) ?: null,
                'type'=>$type, 'value'=>(float)$value, 'start'=>$start, 'end'=>$end, 'max_uses'=>$maxUses, 'user'=>$user['id']
            ]);
        } catch (\PDOException) {
            Response::error('Ce code promotionnel existe déjà.', 409);
        }
        Response::success(['id'=>(int)$this->pdo->lastInsertId()], 'Promotion créée.', 201);
    }

    public function update(array $params): never
    {
        $this->auth->requireRoles(['admin']);
        $id = (int)$params['id'];
        $stmt = $this->pdo->prepare('SELECT discount_type,discount_value,starts_at,ends_at FROM promotions WHERE id=:id');
        $stmt->execute(['id'=>$id]);
        $current = $stmt->fetch();
        if (!$current) Response::error('Promotion introuvable.',404);

        $d = Request::json();
        $fields = [];
        $values = ['id'=>$id];

        if (array_key_exists('title',$d)) {
            $title = trim((string)$d['title']);
            if ($title === '') Response::error('Titre invalide.',422);
            $fields[] = 'title=:title';
            $values['title'] = $title;
        }
        if (array_key_exists('description',$d)) {
            $fields[] = 'description=:description';
            $values['description'] = trim((string)$d['description']) ?: null;
        }

        $nextType = $d['discount_type'] ?? $current['discount_type'];
        $nextValue = $d['discount_value'] ?? $current['discount_value'];
        if (!in_array($nextType,['percent','fixed'],true) || !is_numeric($nextValue) || (float)$nextValue <= 0 || ($nextType === 'percent' && (float)$nextValue > 100)) {
            Response::error('Type ou valeur de remise invalide.',422);
        }
        if (array_key_exists('discount_type',$d)) {
            $fields[] = 'discount_type=:discount_type';
            $values['discount_type'] = $nextType;
        }
        if (array_key_exists('discount_value',$d)) {
            $fields[] = 'discount_value=:discount_value';
            $values['discount_value'] = (float)$nextValue;
        }

        $nextStart = array_key_exists('starts_at',$d) ? $this->dateValue($d['starts_at']) : $current['starts_at'];
        $nextEnd = array_key_exists('ends_at',$d) ? $this->dateValue($d['ends_at']) : $current['ends_at'];
        if (!$nextStart || !$nextEnd || $nextEnd <= $nextStart) {
            Response::error('La période de promotion est invalide.',422);
        }
        if (array_key_exists('starts_at',$d)) {
            $fields[] = 'starts_at=:starts_at';
            $values['starts_at'] = $nextStart;
        }
        if (array_key_exists('ends_at',$d)) {
            $fields[] = 'ends_at=:ends_at';
            $values['ends_at'] = $nextEnd;
        }

        if (array_key_exists('max_uses',$d)) {
            $max = $d['max_uses'] === '' || $d['max_uses'] === null ? null : (int)$d['max_uses'];
            if ($max !== null && $max < 1) Response::error('Nombre d’utilisations invalide.',422);
            $fields[] = 'max_uses=:max_uses';
            $values['max_uses'] = $max;
        }
        if (array_key_exists('is_active',$d)) {
            $fields[] = 'is_active=:is_active';
            $values['is_active'] = $d['is_active'] ? 1 : 0;
        }

        if (!$fields) Response::error('Aucune modification fournie.',422);
        $stmt = $this->pdo->prepare('UPDATE promotions SET '.implode(',',$fields).' WHERE id=:id');
        $stmt->execute($values);
        Response::success(null,'Promotion mise à jour.');
    }

    private function dateValue(mixed $value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') return null;
        $value = str_replace('T',' ',$value);
        $time = strtotime($value);
        return $time === false ? null : date('Y-m-d H:i:s',$time);
    }
}
