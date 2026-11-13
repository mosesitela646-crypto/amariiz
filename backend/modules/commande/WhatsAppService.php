<?php
declare(strict_types=1);
namespace Modules\Commande;
use Core\Env;
final class WhatsAppService { public static function link(array $order,array $items): string { $lines=["Bonjour AMARIIZ MILK, voici ma commande #{$order['id']} :"]; foreach($items as $i) $lines[]="- {$i['name']} ({$i['composition']}, {$i['flavor']}, {$i['format']}) x{$i['quantity']} : {$i['unit_price']} FC"; $lines[]="Total : {$order['total']} FC"; $lines[]="Ville : {$order['city_name']}"; $lines[]="Réception : {$order['reception_mode']}"; $number=preg_replace('/\D/','',Env::get('WHATSAPP_NUMBER','243999226344') ?? '243999226344'); return 'https://wa.me/'.$number.'?text='.rawurlencode(implode("\n",$lines)); } }
