<?php
/**
 * Testi del modulo (italiano, riferimento: contiene tutte le chiavi).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

return [
    'yes' => 'Sì',
    'no' => 'No',

    'cfg_legend' => 'Impostazioni',
    'cfg_enabled' => 'Mostra le combinazioni nei listati',
    'cfg_group' => 'Gruppo di attributi da separare',
    'cfg_group_desc' => 'Nel listato compare un elemento per ogni valore di questo gruppo (es. uno per colore). Gli altri attributi (es. taglia) restano da scegliere nella pagina prodotto.',
    'cfg_all_categories' => 'Applica a tutte le categorie',
    'cfg_all_categories_desc' => 'Se attivo, l\'elenco di categorie qui sotto viene ignorato.',
    'cfg_categories' => 'Categorie',
    'cfg_categories_desc' => 'Le combinazioni vengono mostrate solo nei listati delle categorie selezionate. Le sottocategorie non sono incluse automaticamente: vanno selezionate una per una.',
    'cfg_default_all' => 'Prodotti non configurati: mostra tutti i valori',
    'cfg_default_all_desc' => 'Se attivo, i prodotti in cui non hai ancora scelto i valori (scheda «Moduli» del prodotto) vengono mostrati con tutti i valori. Se disattivo, restano un elemento unico finché non scegli i valori.',
    'cfg_hide_oos' => 'Nascondi i valori non disponibili',
    'cfg_hide_oos_desc' => 'Nasconde un valore se nessuna delle sue combinazioni è ordinabile.',
    'cfg_name_value' => 'Aggiungi il valore al nome del prodotto',
    'cfg_name_value_desc' => 'Es. «T-shirt - Blu».',
    'cfg_save' => 'Salva',
    'cfg_saved' => 'Impostazioni salvate.',
    'cfg_error_group' => 'Gruppo di attributi non valido.',
    'cfg_warn_categories' => 'Nessuna categoria selezionata: le combinazioni non vengono mostrate in nessun listato.',
    'cfg_warn_combinations' => 'Le combinazioni sono disattivate nel negozio (Parametri negozio → Impostazioni prodotti): il modulo non ha effetto.',

    'tab_title' => 'Combinazioni nel listato',
    'tab_disabled' => 'Il modulo è disattivato: le scelte vengono salvate ma non hanno effetto finché non lo riattivi.',
    'tab_no_category' => 'Nessuna categoria di questo prodotto è abilitata nel modulo: i valori scelti compariranno solo nei listati delle categorie abilitate.',
    'tab_configure' => 'Configura il modulo',
    'tab_no_values' => 'Questo prodotto non ha combinazioni con il gruppo «{group}»: nel listato viene mostrato come elemento unico.',
    'tab_intro' => 'Scegli quali valori di «{group}» mostrare come elementi separati nel listato. Se non ne scegli nessuno il prodotto viene mostrato come elemento unico.',
    'tab_not_configured_all' => 'Prodotto non ancora configurato: nel listato vengono mostrati tutti i valori (impostazione predefinita del modulo).',
    'tab_not_configured_none' => 'Prodotto non ancora configurato: nel listato viene mostrato come elemento unico (impostazione predefinita del modulo).',
    'tab_select_all' => 'Seleziona tutti',
    'tab_select_none' => 'Nessuno',
    'tab_reset' => 'Ripristina l\'impostazione predefinita',
    'tab_reset_help_all' => 'al salvataggio la scelta viene cancellata e vengono mostrati tutti i valori',
    'tab_reset_help_none' => 'al salvataggio la scelta viene cancellata e il prodotto torna elemento unico',
    'tab_combinations' => 'combinazioni:',
];
