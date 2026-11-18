<?php
/**
 * Combinazioni nel listato — mostra le combinazioni dei prodotti come elementi separati
 * nei listati delle categorie, scegliendo da back office quali valori mostrare per ogni prodotto.
 *
 * @author dotm.it
 * @license MIT (vedi LICENSE)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class DotmCombinationOnListing extends Module
{
    const CFG_ENABLED = 'DOTMCOMBINATIONONLISTING_ENABLED';
    const CFG_GROUP = 'DOTMCOMBINATIONONLISTING_GROUP';
    const CFG_ALL_CATEGORIES = 'DOTMCOMBINATIONONLISTING_ALL_CATEGORIES';
    const CFG_CATEGORIES = 'DOTMCOMBINATIONONLISTING_CATEGORIES';
    const CFG_DEFAULT_ALL = 'DOTMCOMBINATIONONLISTING_DEFAULT_ALL';
    const CFG_HIDE_OUT_OF_STOCK = 'DOTMCOMBINATIONONLISTING_HIDE_OOS';
    const CFG_NAME_WITH_VALUE = 'DOTMCOMBINATIONONLISTING_NAME_VALUE';

    /** tabella con i valori scelti per prodotto; id_attribute = 0 indica «configurato, nessun valore» */
    const TABLE = 'dotmcombinationonlisting_value';

    const HOOKS = [
        'productSearchProvider',
        'actionPresentProductListing',
        'actionPresentProduct',
        'displayAdminProductsExtra',
        'actionProductUpdate',
        'actionAfterUpdateProductFormHandler',
        'actionProductDelete',
    ];

    /** @var array<string, array> */
    protected $strings = [];

    /** @var bool evita la ricorsione quando interroghiamo gli altri provider */
    protected static $collectingProviders = false;

    /** @var bool i valori del prodotto sono già stati salvati in questa richiesta */
    protected static $saved = false;

    public function __construct()
    {
        $this->name = 'dotmcombinationonlisting';
        $this->tab = 'front_office_features';
        $this->version = '1.7.2';
        $this->author = 'dotm.it';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '1.7.7.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = 'Combinazioni nel listato';
        $this->description = 'Mostra le combinazioni dei prodotti come elementi separati nei listati delle categorie (es. un riquadro per colore), con la scelta dei valori da mostrare in ogni prodotto.';
        $this->confirmUninstall = 'Disinstallando il modulo verranno cancellate anche le scelte fatte nei prodotti. Continuare?';
    }

    /* ------------------------------------------------------------------
     * Installazione
     * ------------------------------------------------------------------ */

    public function install()
    {
        return parent::install()
            && $this->installDb()
            && $this->registerHook(self::HOOKS)
            && $this->moveSearchProviderHookFirst()
            && Configuration::updateValue(self::CFG_ENABLED, 1)
            && Configuration::updateValue(self::CFG_GROUP, $this->getDefaultGroupId())
            && Configuration::updateValue(self::CFG_ALL_CATEGORIES, 0)
            && Configuration::updateValue(self::CFG_CATEGORIES, '')
            && Configuration::updateValue(self::CFG_DEFAULT_ALL, 0)
            && Configuration::updateValue(self::CFG_HIDE_OUT_OF_STOCK, 1)
            && Configuration::updateValue(self::CFG_NAME_WITH_VALUE, 1);
    }

    public function uninstall()
    {
        foreach ([
            self::CFG_ENABLED, self::CFG_GROUP, self::CFG_ALL_CATEGORIES, self::CFG_CATEGORIES,
            self::CFG_DEFAULT_ALL, self::CFG_HIDE_OUT_OF_STOCK, self::CFG_NAME_WITH_VALUE,
        ] as $key) {
            Configuration::deleteByName($key);
        }

        return parent::uninstall()
            && Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . self::TABLE . '`');
    }

    public function installDb()
    {
        return Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::TABLE . '` (
                `id_product` INT(10) UNSIGNED NOT NULL,
                `id_shop` INT(10) UNSIGNED NOT NULL,
                `id_attribute` INT(10) UNSIGNED NOT NULL,
                PRIMARY KEY (`id_product`, `id_shop`, `id_attribute`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4
        ');
    }

    /**
     * Il modulo deve essere il primo su productSearchProvider: avvolge il provider
     * che sarebbe stato usato (ps_facetedsearch o quello del core).
     */
    public function moveSearchProviderHookFirst()
    {
        $idHook = (int) Hook::getIdByName('productSearchProvider');
        if (!$idHook) {
            return true;
        }
        $db = Db::getInstance();
        $shopIds = $db->executeS('SELECT DISTINCT id_shop FROM `' . _DB_PREFIX_ . 'hook_module` WHERE id_hook = ' . $idHook) ?: [];
        foreach ($shopIds as $shop) {
            $modules = $db->executeS('
                SELECT id_module FROM `' . _DB_PREFIX_ . 'hook_module`
                WHERE id_hook = ' . $idHook . ' AND id_shop = ' . (int) $shop['id_shop'] . '
                ORDER BY (id_module = ' . (int) $this->id . ') DESC, position ASC
            ') ?: [];
            foreach ($modules as $i => $module) {
                $db->update('hook_module', ['position' => $i + 1], 'id_hook = ' . $idHook
                    . ' AND id_shop = ' . (int) $shop['id_shop'] . ' AND id_module = ' . (int) $module['id_module']);
            }
        }

        return true;
    }

    /* ------------------------------------------------------------------
     * Testi (lang/<iso>.php; per le lingue senza file: inglese, poi italiano)
     * ------------------------------------------------------------------ */

    public function getStrings($idLang = null)
    {
        if ($idLang) {
            $iso = Language::getIsoById((int) $idLang);
        } else {
            $iso = isset($this->context->language) && $this->context->language ? $this->context->language->iso_code : 'en';
        }
        $iso = preg_replace('/[^a-z]/', '', strtolower((string) $iso));
        if (!isset($this->strings[$iso])) {
            $strings = include __DIR__ . '/lang/it.php';
            if ($iso !== 'it') {
                $file = __DIR__ . '/lang/' . $iso . '.php';
                foreach (array_unique([__DIR__ . '/lang/en.php', is_file($file) ? $file : __DIR__ . '/lang/en.php']) as $path) {
                    $local = include $path;
                    $strings = array_merge($strings, is_array($local) ? $local : []);
                }
            }
            $this->strings[$iso] = $strings;
        }

        return $this->strings[$iso];
    }

    public function t($key, array $params = [], $idLang = null)
    {
        $strings = $this->getStrings($idLang);
        $text = isset($strings[$key]) ? $strings[$key] : $key;
        if ($params) {
            $replace = [];
            foreach ($params as $k => $v) {
                $replace['{' . $k . '}'] = $v;
            }
            $text = strtr($text, $replace);
        }

        return $text;
    }

    /* ------------------------------------------------------------------
     * Regole
     * ------------------------------------------------------------------ */

    public function getGroupId()
    {
        return (int) Configuration::get(self::CFG_GROUP);
    }

    public function getCategoryIds()
    {
        return array_values(array_unique(array_filter(array_map('intval', explode(',', (string) Configuration::get(self::CFG_CATEGORIES))))));
    }

    public function isEnabledForCategory($idCategory)
    {
        return (bool) Configuration::get(self::CFG_ENABLED)
            && $this->getGroupId() > 0
            && Combination::isFeatureActive()
            && (Configuration::get(self::CFG_ALL_CATEGORIES) || in_array((int) $idCategory, $this->getCategoryIds(), true));
    }

    /* ------------------------------------------------------------------
     * Front office
     * ------------------------------------------------------------------ */

    public function hookProductSearchProvider($params)
    {
        if (self::$collectingProviders || empty($params['query'])) {
            return null;
        }
        $query = $params['query'];
        if ($query->getQueryType() !== 'category' || !$this->isEnabledForCategory($query->getIdCategory())) {
            return null;
        }

        require_once __DIR__ . '/classes/DotmCombinationOnListingSearchProvider.php';

        // Provider che sarebbe stato usato senza questo modulo (es. ps_facetedsearch).
        self::$collectingProviders = true;
        try {
            $providers = Hook::exec('productSearchProvider', ['query' => $query], null, true);
        } finally {
            self::$collectingProviders = false;
        }
        $inner = null;
        foreach (is_array($providers) ? $providers : [] as $provider) {
            if ($provider instanceof PrestaShop\PrestaShop\Core\Product\Search\ProductSearchProviderInterface) {
                $inner = $provider;
                break;
            }
        }
        if (!$inner) {
            $category = new Category((int) $query->getIdCategory(), (int) $this->context->language->id);
            if (!Validate::isLoadedObject($category)) {
                return null;
            }
            $inner = new PrestaShop\PrestaShop\Adapter\Category\CategoryProductSearchProvider($this->context->getTranslator(), $category);
        }

        return new DotmCombinationOnListingSearchProvider($this, $inner);
    }

    public function hookActionPresentProductListing($params)
    {
        if (empty($params['presentedProduct'])) {
            return;
        }
        $product = $params['presentedProduct'];
        if (!isset($product['dcol_id_product_attribute'])) {
            return;
        }
        // Il nome non si scrive nella riga: la cache di Product::getProductProperties() lo sovrascriverebbe.
        if (isset($product['dcol_name']) && $product['dcol_name'] !== '') {
            $this->setPresentedValue($product, 'name', $product['dcol_name']);
        }
        // Copertina della combinazione (PS 8.1+ usa la copertina del prodotto se
        // PS_USE_COMBINATION_IMAGE_IN_LISTING è disattivato).
        if (!empty($product['dcol_id_image']) && !empty($product['images'])) {
            foreach ($product['images'] as $image) {
                if (isset($image['id_image']) && (int) $image['id_image'] === (int) $product['dcol_id_image']) {
                    $this->setPresentedValue($product, 'cover', $image);
                    break;
                }
            }
        }
    }

    public function hookActionPresentProduct($params)
    {
        $this->hookActionPresentProductListing($params);
    }

    protected function setPresentedValue($product, $key, $value)
    {
        if (is_object($product) && method_exists($product, 'offsetSet')) {
            $product->offsetSet($key, $value, true);
        }
    }

    /**
     * Espande i prodotti in voci del listato, una per valore scelto del gruppo configurato.
     *
     * @param int[] $productIds id ordinati come nel listato
     * @param array $selectedAtts [id_attribute_group => [id_attribute => id_attribute]] dai filtri attivi
     *
     * @return array voci [id_product, id_product_attribute (0 = prodotto non espanso), attribute_name]
     */
    public function expandProducts(array $productIds, array $selectedAtts, $idLang)
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if (!$productIds) {
            return [];
        }

        $idGroup = $this->getGroupId();
        $hideOutOfStock = (bool) Configuration::get(self::CFG_HIDE_OUT_OF_STOCK);
        $defaultAll = (bool) Configuration::get(self::CFG_DEFAULT_ALL);
        $groupIds = array_unique(array_merge([$idGroup], array_map('intval', array_keys($selectedAtts))));
        $chosen = $this->getChosenValues($productIds);

        // Prodotti da espandere: quelli configurati con almeno un valore, o tutti se «tutti i valori» è predefinito.
        $candidates = [];
        foreach ($productIds as $idProduct) {
            if (isset($chosen[$idProduct]) ? (bool) array_filter($chosen[$idProduct]) : $defaultAll) {
                $candidates[] = $idProduct;
            }
        }

        $combinations = [];
        $valuesOrder = [];
        $valueNames = [];
        foreach ($candidates ? $this->getCombinationRows($candidates, $groupIds, (int) $idLang) : [] as $row) {
            $idProduct = (int) $row['id_product'];
            $ipa = (int) $row['id_product_attribute'];
            $idAttribute = (int) $row['id_attribute'];
            if (!isset($combinations[$idProduct][$ipa])) {
                $combinations[$idProduct][$ipa] = [
                    'default_on' => (int) $row['default_on'],
                    'quantity' => (int) $row['quantity'],
                    'out_of_stock' => (int) $row['out_of_stock'],
                    'attributes' => [],
                ];
            }
            $combinations[$idProduct][$ipa]['attributes'][(int) $row['id_attribute_group']] = $idAttribute;
            if ((int) $row['id_attribute_group'] === $idGroup) {
                $valuesOrder[$idProduct][$idAttribute] = $idAttribute;
                $valueNames[$idAttribute] = (string) $row['attribute_name'];
            }
        }

        $entries = [];
        foreach ($productIds as $idProduct) {
            $productEntries = [];
            if (isset($combinations[$idProduct], $valuesOrder[$idProduct])) {
                $byValue = [];
                foreach ($combinations[$idProduct] as $ipa => $combination) {
                    if (isset($combination['attributes'][$idGroup])
                        && $this->matchesSelection($combination['attributes'], $selectedAtts)
                    ) {
                        $byValue[$combination['attributes'][$idGroup]][$ipa] = $combination;
                    }
                }
                $allowed = isset($chosen[$idProduct]) ? $chosen[$idProduct] : null; // null = tutti
                foreach ($valuesOrder[$idProduct] as $idAttribute) {
                    if (empty($byValue[$idAttribute]) || ($allowed !== null && !isset($allowed[$idAttribute]))) {
                        continue;
                    }
                    $ipa = $this->pickCombination($byValue[$idAttribute], $hideOutOfStock);
                    if ($ipa) {
                        $productEntries[] = [
                            'id_product' => $idProduct,
                            'id_product_attribute' => $ipa,
                            'attribute_name' => $valueNames[$idAttribute],
                        ];
                    }
                }
            }
            // Prodotto non da espandere, o nessun valore utilizzabile: resta un riquadro singolo.
            if (!$productEntries) {
                $productEntries[] = ['id_product' => $idProduct, 'id_product_attribute' => 0, 'attribute_name' => ''];
            }
            foreach ($productEntries as $entry) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    /**
     * @param array $baseRows righe prodotto del provider, indicizzate per id_product
     *
     * @return array una riga per voce, nell'ordine delle voci
     */
    public function buildListingRows(array $entries, array $baseRows, $idLang)
    {
        $combinationIds = array_filter(array_column($entries, 'id_product_attribute'));
        $images = $combinationIds ? $this->getCombinationImages($combinationIds, (int) $idLang) : [];
        $withName = (bool) Configuration::get(self::CFG_NAME_WITH_VALUE);

        $rows = [];
        foreach ($entries as $entry) {
            if (!isset($baseRows[$entry['id_product']])) {
                continue;
            }
            $row = $baseRows[$entry['id_product']];
            $ipa = (int) $entry['id_product_attribute'];
            if ($ipa) {
                $row['id_product_attribute'] = $ipa;
                $row['dcol_id_product_attribute'] = $ipa;
                if (isset($images[$ipa])) {
                    $row['id_image'] = $row['cover_image_id'] = (int) $images[$ipa]['id_image'];
                    $row['legend'] = $images[$ipa]['legend'];
                    $row['dcol_id_image'] = (int) $images[$ipa]['id_image'];
                }
                if ($withName && $entry['attribute_name'] !== '') {
                    $name = isset($row['name']) ? (string) $row['name'] : (string) Product::getProductName((int) $entry['id_product'], null, (int) $idLang);
                    $row['dcol_name'] = $name . ' - ' . $entry['attribute_name'];
                }
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /** Prima immagine associata a ogni combinazione, con precedenza alla copertina. */
    protected function getCombinationImages(array $combinationIds, $idLang)
    {
        $rows = Db::getInstance()->executeS('
            SELECT pai.id_product_attribute, i.id_image, il.legend
            FROM `' . _DB_PREFIX_ . 'product_attribute_image` pai
            INNER JOIN `' . _DB_PREFIX_ . 'image` i ON i.id_image = pai.id_image
            LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON il.id_image = i.id_image AND il.id_lang = ' . (int) $idLang . '
            WHERE pai.id_product_attribute IN (' . implode(',', array_map('intval', $combinationIds)) . ')
            ORDER BY i.cover DESC, i.position ASC
        ') ?: [];

        $images = [];
        foreach ($rows as $row) {
            if (!isset($images[(int) $row['id_product_attribute']])) {
                $images[(int) $row['id_product_attribute']] = $row;
            }
        }

        return $images;
    }

    protected function getCombinationRows(array $productIds, array $groupIds, $idLang)
    {
        return Db::getInstance()->executeS('
            SELECT pa.id_product, pa.id_product_attribute, product_attribute_shop.default_on,
                a.id_attribute, a.id_attribute_group, al.name AS attribute_name,
                IFNULL(stock.quantity, 0) AS quantity, IFNULL(stock.out_of_stock, 2) AS out_of_stock
            FROM `' . _DB_PREFIX_ . 'product_attribute` pa
            ' . Shop::addSqlAssociation('product_attribute', 'pa') . '
            INNER JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac
                ON pac.id_product_attribute = pa.id_product_attribute
            INNER JOIN `' . _DB_PREFIX_ . 'attribute` a
                ON a.id_attribute = pac.id_attribute
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al
                ON al.id_attribute = a.id_attribute AND al.id_lang = ' . (int) $idLang . '
            ' . Product::sqlStock('pa', 'pa') . '
            WHERE pa.id_product IN (' . implode(',', array_map('intval', $productIds)) . ')
            AND a.id_attribute_group IN (' . implode(',', array_map('intval', $groupIds)) . ')
            ORDER BY a.position ASC, a.id_attribute ASC, pa.id_product_attribute ASC
        ') ?: [];
    }

    protected function matchesSelection(array $attributes, array $selectedAtts)
    {
        foreach ($selectedAtts as $idGroup => $selected) {
            if (!isset($attributes[$idGroup]) || !isset($selected[$attributes[$idGroup]])) {
                return false;
            }
        }

        return true;
    }

    /** Combinazione di default se ordinabile, poi la prima ordinabile; 0 = valore da nascondere. */
    protected function pickCombination(array $combinations, $hideOutOfStock)
    {
        $first = $default = $firstOrderable = 0;
        foreach ($combinations as $ipa => $combination) {
            $first = $first ?: $ipa;
            if ($combination['default_on']) {
                $default = $ipa;
            }
            if (!$firstOrderable && $this->isOrderable($combination)) {
                $firstOrderable = $ipa;
            }
        }
        if ($default && $this->isOrderable($combinations[$default])) {
            return $default;
        }
        if ($firstOrderable) {
            return $firstOrderable;
        }

        return $hideOutOfStock ? 0 : ($default ?: $first);
    }

    protected function isOrderable(array $combination)
    {
        return !Configuration::get('PS_STOCK_MANAGEMENT')
            || $combination['quantity'] > 0
            || Product::isAvailableWhenOutOfStock($combination['out_of_stock']);
    }

    /* ------------------------------------------------------------------
     * Scelte per prodotto
     * ------------------------------------------------------------------ */

    /** @return array [id_product => [id_attribute => id_attribute]] per i soli prodotti configurati */
    public function getChosenValues(array $productIds)
    {
        $productIds = array_filter(array_map('intval', $productIds));
        if (!$productIds) {
            return [];
        }
        $rows = Db::getInstance()->executeS('
            SELECT id_product, id_attribute FROM `' . _DB_PREFIX_ . self::TABLE . '`
            WHERE id_shop = ' . $this->getShopId() . '
            AND id_product IN (' . implode(',', $productIds) . ')
        ') ?: [];

        $chosen = [];
        foreach ($rows as $row) {
            $idAttribute = (int) $row['id_attribute'];
            if (!isset($chosen[(int) $row['id_product']])) {
                $chosen[(int) $row['id_product']] = [];
            }
            if ($idAttribute) {
                $chosen[(int) $row['id_product']][$idAttribute] = $idAttribute;
            }
        }

        return $chosen;
    }

    /** Salva i valori scelti; un elenco vuoto viene salvato come «nessun valore» (id_attribute = 0). */
    public function saveChosenValues($idProduct, array $attributeIds)
    {
        $idProduct = (int) $idProduct;
        $idShop = $this->getShopId();
        $db = Db::getInstance();
        $db->delete(self::TABLE, 'id_product = ' . $idProduct . ' AND id_shop = ' . $idShop);

        $ids = array_values(array_unique(array_filter(array_map('intval', $attributeIds))));
        if (!$ids) {
            $ids = [0];
        }
        $rows = [];
        foreach ($ids as $idAttribute) {
            $rows[] = ['id_product' => $idProduct, 'id_shop' => $idShop, 'id_attribute' => $idAttribute];
        }

        return $db->insert(self::TABLE, $rows);
    }

    public function resetChosenValues($idProduct)
    {
        return Db::getInstance()->delete(self::TABLE, 'id_product = ' . (int) $idProduct . ' AND id_shop = ' . $this->getShopId());
    }

    protected function getShopId()
    {
        return (int) $this->context->shop->id ?: (int) Configuration::get('PS_SHOP_DEFAULT');
    }

    /* ------------------------------------------------------------------
     * Back office: scheda «Moduli» del prodotto
     * ------------------------------------------------------------------ */

    public function hookDisplayAdminProductsExtra($params)
    {
        $idProduct = isset($params['id_product']) ? (int) $params['id_product'] : (int) Tools::getValue('id_product');
        $idGroup = $this->getGroupId();
        $idLang = (int) $this->context->language->id;
        $chosen = $this->getChosenValues([$idProduct]);
        $configured = isset($chosen[$idProduct]);
        $defaultAll = (bool) Configuration::get(self::CFG_DEFAULT_ALL);

        $values = $idProduct ? $this->getProductGroupValues($idProduct, $idGroup, $idLang) : [];
        foreach ($values as &$value) {
            $value['checked'] = $configured ? isset($chosen[$idProduct][(int) $value['id_attribute']]) : $defaultAll;
        }
        unset($value);

        $group = new AttributeGroup($idGroup, $idLang);
        $groupName = Validate::isLoadedObject($group) ? (string) $group->name : '';
        $categoryEnabled = (bool) Configuration::get(self::CFG_ALL_CATEGORIES)
            || ($idProduct && array_intersect(array_map('intval', Product::getProductCategories($idProduct)), $this->getCategoryIds()));

        $this->context->smarty->assign([
            'dcol' => [
                'product_id' => $idProduct,
                'values' => $values,
                'configured' => $configured,
                'module_enabled' => (bool) Configuration::get(self::CFG_ENABLED),
                'category_enabled' => $categoryEnabled,
                'config_url' => $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]),
            ],
            'dcol_t' => [
                'title' => $this->t('tab_title'),
                'disabled' => $this->t('tab_disabled'),
                'no_category' => $this->t('tab_no_category'),
                'configure' => $this->t('tab_configure'),
                'no_values' => $this->t('tab_no_values', ['group' => $groupName]),
                'intro' => $this->t('tab_intro', ['group' => $groupName]),
                'not_configured' => $this->t($defaultAll ? 'tab_not_configured_all' : 'tab_not_configured_none'),
                'select_all' => $this->t('tab_select_all'),
                'select_none' => $this->t('tab_select_none'),
                'reset' => $this->t('tab_reset'),
                'reset_help' => $this->t($defaultAll ? 'tab_reset_help_all' : 'tab_reset_help_none'),
                'combinations' => $this->t('tab_combinations'),
            ],
        ]);

        return $this->display(__FILE__, 'views/templates/admin/product_tab.tpl');
    }

    /** Pagina prodotto di PS 1.7 / 8.0 (e chiamata anche dal salvataggio di PS 8.1+). */
    public function hookActionProductUpdate($params)
    {
        $this->saveFromProductForm(isset($params['id_product']) ? (int) $params['id_product'] : 0);
    }

    /** Nuova pagina prodotto (PS 8.1+). */
    public function hookActionAfterUpdateProductFormHandler($params)
    {
        $this->saveFromProductForm(isset($params['id']) ? (int) $params['id'] : 0);
    }

    protected function saveFromProductForm($idProduct)
    {
        // Senza il campo marcatore la scheda non è stata inviata: si tengono le scelte salvate.
        if (!$idProduct || self::$saved || !Tools::getValue('dcol_product_form')) {
            return;
        }
        if ((int) Tools::getValue('dcol_product_form') !== $idProduct) {
            return;
        }
        self::$saved = true;

        if (Tools::getValue('dcol_reset')) {
            $this->resetChosenValues($idProduct);

            return;
        }
        $values = Tools::getValue('dcol_values');
        $this->saveChosenValues($idProduct, is_array($values) ? $values : []);
    }

    public function hookActionProductDelete($params)
    {
        if (!empty($params['id_product'])) {
            Db::getInstance()->delete(self::TABLE, 'id_product = ' . (int) $params['id_product']);
        }
    }

    /** Valori del gruppo configurato presenti nelle combinazioni del prodotto, con il numero di combinazioni. */
    protected function getProductGroupValues($idProduct, $idGroup, $idLang)
    {
        if (!$idGroup) {
            return [];
        }

        return Db::getInstance()->executeS('
            SELECT a.id_attribute, al.name, a.color, COUNT(DISTINCT pa.id_product_attribute) AS combinations
            FROM `' . _DB_PREFIX_ . 'product_attribute` pa
            ' . Shop::addSqlAssociation('product_attribute', 'pa') . '
            INNER JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac
                ON pac.id_product_attribute = pa.id_product_attribute
            INNER JOIN `' . _DB_PREFIX_ . 'attribute` a
                ON a.id_attribute = pac.id_attribute AND a.id_attribute_group = ' . (int) $idGroup . '
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al
                ON al.id_attribute = a.id_attribute AND al.id_lang = ' . (int) $idLang . '
            WHERE pa.id_product = ' . (int) $idProduct . '
            GROUP BY a.id_attribute, al.name, a.color, a.position
            ORDER BY a.position ASC, a.id_attribute ASC
        ') ?: [];
    }

    protected function getDefaultGroupId()
    {
        return (int) Db::getInstance()->getValue('
            SELECT id_attribute_group FROM `' . _DB_PREFIX_ . 'attribute_group`
            WHERE is_color_group = 1
            ORDER BY position ASC
        ');
    }

    /* ------------------------------------------------------------------
     * Configurazione
     * ------------------------------------------------------------------ */

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submitDotmCombinationOnListing')) {
            $idGroup = (int) Tools::getValue(self::CFG_GROUP);
            if (!Validate::isLoadedObject(new AttributeGroup($idGroup))) {
                $output .= $this->displayError($this->t('cfg_error_group'));
            } else {
                $categoryIds = Tools::getValue(self::CFG_CATEGORIES);
                $categoryIds = is_array($categoryIds) ? array_unique(array_filter(array_map('intval', $categoryIds))) : [];

                Configuration::updateValue(self::CFG_ENABLED, (int) (bool) Tools::getValue(self::CFG_ENABLED));
                Configuration::updateValue(self::CFG_GROUP, $idGroup);
                Configuration::updateValue(self::CFG_ALL_CATEGORIES, (int) (bool) Tools::getValue(self::CFG_ALL_CATEGORIES));
                Configuration::updateValue(self::CFG_CATEGORIES, implode(',', $categoryIds));
                Configuration::updateValue(self::CFG_DEFAULT_ALL, (int) (bool) Tools::getValue(self::CFG_DEFAULT_ALL));
                Configuration::updateValue(self::CFG_HIDE_OUT_OF_STOCK, (int) (bool) Tools::getValue(self::CFG_HIDE_OUT_OF_STOCK));
                Configuration::updateValue(self::CFG_NAME_WITH_VALUE, (int) (bool) Tools::getValue(self::CFG_NAME_WITH_VALUE));
                $this->moveSearchProviderHookFirst();
                $output .= $this->displayConfirmation($this->t('cfg_saved'));
            }
        }

        if (!Combination::isFeatureActive()) {
            $output .= $this->displayWarning($this->t('cfg_warn_combinations'));
        }
        if (!Configuration::get(self::CFG_ALL_CATEGORIES) && !$this->getCategoryIds()) {
            $output .= $this->displayWarning($this->t('cfg_warn_categories'));
        }

        return $output . $this->renderForm();
    }

    protected function renderForm()
    {
        $switch = [
            ['id' => 'active_on', 'value' => 1, 'label' => $this->t('yes')],
            ['id' => 'active_off', 'value' => 0, 'label' => $this->t('no')],
        ];
        $idLang = (int) $this->context->language->id;

        $form = [
            'form' => [
                'legend' => ['title' => $this->t('cfg_legend'), 'icon' => 'icon-cogs'],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->t('cfg_enabled'),
                        'name' => self::CFG_ENABLED,
                        'is_bool' => true,
                        'values' => $switch,
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->t('cfg_group'),
                        'name' => self::CFG_GROUP,
                        'desc' => $this->t('cfg_group_desc'),
                        'options' => [
                            'query' => AttributeGroup::getAttributesGroups($idLang),
                            'id' => 'id_attribute_group',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->t('cfg_all_categories'),
                        'name' => self::CFG_ALL_CATEGORIES,
                        'desc' => $this->t('cfg_all_categories_desc'),
                        'is_bool' => true,
                        'values' => $switch,
                    ],
                    [
                        'type' => 'categories',
                        'label' => $this->t('cfg_categories'),
                        'name' => self::CFG_CATEGORIES,
                        'desc' => $this->t('cfg_categories_desc'),
                        'tree' => [
                            'id' => 'dcol-categories-tree',
                            'selected_categories' => $this->getCategoryIds(),
                            'use_checkbox' => true,
                            'use_search' => true,
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->t('cfg_default_all'),
                        'name' => self::CFG_DEFAULT_ALL,
                        'desc' => $this->t('cfg_default_all_desc'),
                        'is_bool' => true,
                        'values' => $switch,
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->t('cfg_hide_oos'),
                        'name' => self::CFG_HIDE_OUT_OF_STOCK,
                        'desc' => $this->t('cfg_hide_oos_desc'),
                        'is_bool' => true,
                        'values' => $switch,
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->t('cfg_name_value'),
                        'name' => self::CFG_NAME_WITH_VALUE,
                        'desc' => $this->t('cfg_name_value_desc'),
                        'is_bool' => true,
                        'values' => $switch,
                    ],
                ],
                'submit' => ['title' => $this->t('cfg_save')],
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = $idLang;
        $helper->submit_action = 'submitDotmCombinationOnListing';
        $helper->fields_value = [
            self::CFG_ENABLED => (int) Configuration::get(self::CFG_ENABLED),
            self::CFG_GROUP => $this->getGroupId(),
            self::CFG_ALL_CATEGORIES => (int) Configuration::get(self::CFG_ALL_CATEGORIES),
            self::CFG_DEFAULT_ALL => (int) Configuration::get(self::CFG_DEFAULT_ALL),
            self::CFG_HIDE_OUT_OF_STOCK => (int) Configuration::get(self::CFG_HIDE_OUT_OF_STOCK),
            self::CFG_NAME_WITH_VALUE => (int) Configuration::get(self::CFG_NAME_WITH_VALUE),
        ];

        return $helper->generateForm([$form]);
    }
}
