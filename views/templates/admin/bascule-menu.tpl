{*
 * freshapptarteaucitron
 * @author FreshApp.io
 * @copyright 2026 FreshApp.io
 * @license Proprietary - see LICENSE file
 *}
<div class="panel">
  <form method="post" style="display:flex;align-items:center;justify-content:flex-end;gap:12px;margin:0">
    <span>{$fa_tac_label_menu|escape:'html':'UTF-8'}</span>
    <input type="hidden" name="submitTacBoMenu" value="1">
    <span class="switch prestashop-switch fixed-width-lg">
      <input type="radio" name="tac_bo_menu" id="tac_bo_menu_on" value="1"{if $fa_tac_menu_actif} checked="checked"{/if}{if !empty($fa_menu_demo)} disabled{/if} onchange="this.form.submit()">
      <label for="tac_bo_menu_on">{$fa_tac_oui|escape:'html':'UTF-8'}</label>
      <input type="radio" name="tac_bo_menu" id="tac_bo_menu_off" value="0"{if !$fa_tac_menu_actif} checked="checked"{/if}{if !empty($fa_menu_demo)} disabled{/if} onchange="this.form.submit()">
      <label for="tac_bo_menu_off">{$fa_tac_non|escape:'html':'UTF-8'}</label>
      <a class="slide-button btn"></a>
    </span>
    {if !empty($fa_menu_demo)}<small class="text-muted">{l s='Verrouillé en mode démonstration' mod='freshapptarteaucitron'}</small>{/if}
  </form>
</div>
