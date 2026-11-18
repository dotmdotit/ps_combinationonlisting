<?php
/**
 * Provider di ricerca che avvolge quello che PrestaShop userebbe per la categoria
 * (ps_facetedsearch o il provider del core) ed espande i prodotti in combinazioni.
 *
 * @author dotm.it
 * @license MIT (vedi LICENSE)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchProviderInterface;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;

class DotmCombinationOnListingSearchProvider implements ProductSearchProviderInterface
{
    /** prodotti letti in un'unica pagina dal provider interno */
    const MAX_PRODUCTS = 100000;

    /** @var DotmCombinationOnListing */
    protected $module;

    /** @var ProductSearchProviderInterface */
    protected $inner;

    public function __construct(DotmCombinationOnListing $module, ProductSearchProviderInterface $inner)
    {
        $this->module = $module;
        $this->inner = $inner;
    }

    public function runQuery(ProductSearchContext $context, ProductSearchQuery $query)
    {
        $sortOrder = $query->getSortOrder();
        if ($sortOrder && method_exists($sortOrder, 'isRandom') && $sortOrder->isRandom()) {
            return $this->inner->runQuery($context, $query);
        }

        // Il provider interno pagina per prodotto: si legge l'intero elenco ordinato
        // e si pagina sulle voci espanse.
        $fullQuery = clone $query;
        $fullQuery->setPage(1)->setResultsPerPage(self::MAX_PRODUCTS);
        $result = $this->inner->runQuery($context, $fullQuery);

        $baseRows = [];
        foreach ((array) $result->getProducts() as $row) {
            if (isset($row['id_product'])) {
                $baseRows[(int) $row['id_product']] = $row;
            }
        }
        if (!$baseRows) {
            return $result;
        }

        $entries = $this->module->expandProducts(array_keys($baseRows), $this->getSelectedAttributes($result), $context->getIdLang());
        $perPage = max(1, (int) $query->getResultsPerPage());
        $page = max(1, (int) $query->getPage());
        $pageEntries = array_slice($entries, ($page - 1) * $perPage, $perPage);

        $result->setProducts($this->module->buildListingRows($pageEntries, $baseRows, $context->getIdLang()));
        $result->setTotalProductsCount(count($entries));

        return $result;
    }

    /**
     * Valori di attributo selezionati nei filtri (facet di tipo attribute_group di ps_facetedsearch),
     * per mostrare solo le combinazioni che li rispettano.
     *
     * @return array [id_attribute_group => [id_attribute => id_attribute]]
     */
    protected function getSelectedAttributes($result)
    {
        $selected = [];
        $collection = method_exists($result, 'getFacetCollection') ? $result->getFacetCollection() : null;
        if (!$collection || !method_exists($collection, 'getFacets')) {
            return $selected;
        }
        foreach ($collection->getFacets() as $facet) {
            if ($facet->getType() !== 'attribute_group') {
                continue;
            }
            $idGroup = (int) $facet->getProperty('id_attribute_group');
            if (!$idGroup) {
                continue;
            }
            foreach ($facet->getFilters() as $filter) {
                if ($filter->isActive()) {
                    $idAttribute = (int) $filter->getValue();
                    $selected[$idGroup][$idAttribute] = $idAttribute;
                }
            }
        }

        return $selected;
    }
}
