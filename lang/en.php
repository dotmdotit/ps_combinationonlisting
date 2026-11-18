<?php
/**
 * Module texts (English). Also used for languages without their own file; missing keys fall back to it.php.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

return [
    'yes' => 'Yes',
    'no' => 'No',

    'cfg_legend' => 'Settings',
    'cfg_enabled' => 'Show combinations in listings',
    'cfg_group' => 'Attribute group to split',
    'cfg_group_desc' => 'The listing shows one item per value of this group (e.g. one per colour). Other attributes (e.g. size) are still chosen on the product page.',
    'cfg_all_categories' => 'Apply to all categories',
    'cfg_all_categories_desc' => 'When enabled, the category list below is ignored.',
    'cfg_categories' => 'Categories',
    'cfg_categories_desc' => 'Combinations are shown only in the listings of the selected categories. Subcategories are not included automatically: select them one by one.',
    'cfg_default_all' => 'Products not configured: show all values',
    'cfg_default_all_desc' => 'When enabled, products whose values you have not chosen yet (product "Modules" tab) are shown with all values. When disabled, they stay a single item until you choose the values.',
    'cfg_hide_oos' => 'Hide unavailable values',
    'cfg_hide_oos_desc' => 'Hides a value when none of its combinations can be ordered.',
    'cfg_name_value' => 'Append the value to the product name',
    'cfg_name_value_desc' => 'E.g. "T-shirt - Blue".',
    'cfg_save' => 'Save',
    'cfg_saved' => 'Settings saved.',
    'cfg_error_group' => 'Invalid attribute group.',
    'cfg_warn_categories' => 'No category selected: combinations are not shown in any listing.',
    'cfg_warn_combinations' => 'Combinations are disabled in the shop (Shop parameters → Product settings): the module has no effect.',

    'tab_title' => 'Combinations in listing',
    'tab_disabled' => 'The module is disabled: your choices are saved but have no effect until you enable it again.',
    'tab_no_category' => 'None of this product\'s categories is enabled in the module: the chosen values will only appear in the listings of enabled categories.',
    'tab_configure' => 'Configure the module',
    'tab_no_values' => 'This product has no combinations with the "{group}" group: it is shown as a single item in the listing.',
    'tab_intro' => 'Choose which "{group}" values to show as separate items in the listing. If you choose none, the product is shown as a single item.',
    'tab_not_configured_all' => 'Product not configured yet: the listing shows all values (module default).',
    'tab_not_configured_none' => 'Product not configured yet: the listing shows it as a single item (module default).',
    'tab_select_all' => 'Select all',
    'tab_select_none' => 'None',
    'tab_reset' => 'Restore the default setting',
    'tab_reset_help_all' => 'on save the choice is cleared and all values are shown',
    'tab_reset_help_none' => 'on save the choice is cleared and the product becomes a single item again',
    'tab_combinations' => 'combinations:',
];
