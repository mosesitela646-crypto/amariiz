<?php
declare(strict_types=1);

namespace Modules\Commande;

use Core\Env;

final class WhatsAppService
{
    public static function link(array $order, array $items): string
    {
        $lines = ["Bonjour AMARIIZ MILK, voici ma commande #{$order['id']} :"];
        foreach ($items as $item) {
            $lines[] = "- {$item['name']} ({$item['composition']}, {$item['flavor']}, {$item['format']}) x{$item['quantity']} : {$item['unit_price']} FC";
        }
        if ((float)($order['discount_amount'] ?? 0) > 0) {
            $lines[] = "Remise promotionnelle : -{$order['discount_amount']} FC";
        }
        $lines[] = "Total : {$order['total']} FC";
        $lines[] = "Ville : {$order['city_name']}";
        $lines[] = "Réception : {$order['reception_mode']}";
        $number = preg_replace('/\D/', '', Env::get('WHATSAPP_NUMBER','243999226344') ?? '243999226344');
        return 'https://wa.me/'.$number.'?text='.rawurlencode(implode("\n",$lines));
    }
}
