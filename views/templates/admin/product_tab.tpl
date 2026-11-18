{**
 * Scheda «Moduli» del prodotto: valori da mostrare come elementi separati nel listato.
 *
 * @author dotm.it
 * @license MIT (vedi LICENSE)
 *}
<div id="dcol-product-tab" class="dcol-product-tab">
  <input type="hidden" name="dcol_product_form" value="{$dcol.product_id|intval}">

  <h3>{$dcol_t.title|escape:'html':'UTF-8'}</h3>

  {if !$dcol.module_enabled}
    <div class="alert alert-warning" role="alert">
      <p class="alert-text">
        {$dcol_t.disabled|escape:'html':'UTF-8'}
        <a href="{$dcol.config_url|escape:'html':'UTF-8'}" target="_blank">{$dcol_t.configure|escape:'html':'UTF-8'}</a>
      </p>
    </div>
  {elseif !$dcol.category_enabled}
    <div class="alert alert-info" role="alert">
      <p class="alert-text">
        {$dcol_t.no_category|escape:'html':'UTF-8'}
        <a href="{$dcol.config_url|escape:'html':'UTF-8'}" target="_blank">{$dcol_t.configure|escape:'html':'UTF-8'}</a>
      </p>
    </div>
  {/if}

  {if !$dcol.values}
    <p class="text-muted">{$dcol_t.no_values|escape:'html':'UTF-8'}</p>
  {else}
    <p>{$dcol_t.intro|escape:'html':'UTF-8'}</p>
    {if !$dcol.configured}
      <p class="text-muted"><em>{$dcol_t.not_configured|escape:'html':'UTF-8'}</em></p>
    {/if}

    <div class="mb-2" style="margin-bottom:8px">
      <button type="button" class="btn btn-outline-secondary btn-default btn-sm" data-dcol-check="1">{$dcol_t.select_all|escape:'html':'UTF-8'}</button>
      <button type="button" class="btn btn-outline-secondary btn-default btn-sm" data-dcol-check="0">{$dcol_t.select_none|escape:'html':'UTF-8'}</button>
    </div>

    <div class="row">
      {foreach $dcol.values as $value}
        <div class="col-md-4 col-sm-6">
          <div class="form-check checkbox">
            <input class="form-check-input" type="checkbox" name="dcol_values[]"
                   id="dcol-value-{$value.id_attribute|intval}" value="{$value.id_attribute|intval}"{if $value.checked} checked{/if}>
            <label class="form-check-label" for="dcol-value-{$value.id_attribute|intval}">
              {if $value.color}
                <span style="display:inline-block;width:12px;height:12px;border:1px solid #bbb;vertical-align:middle;background:{$value.color|escape:'html':'UTF-8'}"></span>
              {/if}
              {$value.name|escape:'html':'UTF-8'}
              <small class="text-muted">({$dcol_t.combinations|escape:'html':'UTF-8'} {$value.combinations|intval})</small>
            </label>
          </div>
        </div>
      {/foreach}
    </div>

    {if $dcol.configured}
      <div class="form-check checkbox" style="margin-top:12px">
        <input class="form-check-input" type="checkbox" name="dcol_reset" id="dcol-reset" value="1">
        <label class="form-check-label" for="dcol-reset">
          {$dcol_t.reset|escape:'html':'UTF-8'}
          <small class="text-muted">— {$dcol_t.reset_help|escape:'html':'UTF-8'}</small>
        </label>
      </div>
    {/if}

    <script>
      (function () {
        var tab = document.getElementById('dcol-product-tab');
        if (!tab) {
          return;
        }
        tab.querySelectorAll('[data-dcol-check]').forEach(function (button) {
          button.addEventListener('click', function () {
            var checked = button.getAttribute('data-dcol-check') === '1';
            tab.querySelectorAll('input[name="dcol_values[]"]').forEach(function (input) {
              if (input.checked !== checked) {
                input.checked = checked;
                input.dispatchEvent(new Event('change', { bubbles: true }));
              }
            });
          });
        });
      })();
    </script>
  {/if}
</div>
