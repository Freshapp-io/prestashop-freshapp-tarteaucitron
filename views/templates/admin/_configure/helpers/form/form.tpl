{*
 * FreshApp Tarteaucitron
 * @author FreshApp.io
 * @copyright 2026 FreshApp.io
 * @license Proprietary - see LICENSE file
 *}
{extends file="helpers/form/form.tpl"}

{* Material Icons (police du back-office PrestaShop 9) au lieu des classes icon-* de FontAwesome *}
{block name="legend"}
  <div class="panel-heading">
    {if isset($field.icon)}<i class="material-icons" style="vertical-align:middle;font-size:18px">{$field.icon|escape:'html':'UTF-8'}</i>{/if}
    {$field.title|escape:'html':'UTF-8'}
  </div>
{/block}

{* Grille 3/9 sur tous les panneaux du module (PrestaShop : 4/8), alignée sur le panneau cache *}
{block name="label"}
  {if isset($input.label)}
    <label class="control-label col-lg-3">{$input.label|escape:'html':'UTF-8'}</label>
  {/if}
{/block}

{* Tag d'installation : repliable quand un identifiant est déjà enregistré, il ne sert plus alors
   qu'à le remplacer. <details> natif, sans JS (le JS de module s'exécute avant le DOM). *}
{block name="input_row"}
  {if $input.name == 'FA_TAC_TAG'}
    <div class="form-group">
      <label class="control-label col-lg-3" for="FA_TAC_TAG">{$input.label|escape:'html':'UTF-8'}</label>
      <div class="col-lg-9">
        {if !empty($fields_value.FA_TAC_UUID)}
          <details class="fa-tac-tag-toggle">
            <summary style="cursor:pointer;padding-top:7px;color:#25b9d7">
              {l s='Paste a new installation tag' d='Modules.Freshapptarteaucitron.Admin'}
            </summary>
        {/if}
        <textarea name="FA_TAC_TAG" id="FA_TAC_TAG" rows="4" class="textarea-autosize" style="margin-top:8px"></textarea>
        <div class="help-block fa-tac-tag-help">
          <p>
            {l s='Paste the installation tag given in your tarteaucitron.io account: the identifier (UUID) and the domain are extracted automatically when you save.' d='Modules.Freshapptarteaucitron.Admin'}
            <a href="https://tarteaucitron.io/dashboard/#account" target="_blank" rel="noopener noreferrer">https://tarteaucitron.io/dashboard/#account</a>
          </p>
          <p>{l s='The tag looks like this:' d='Modules.Freshapptarteaucitron.Admin'}</p>
          <pre style="white-space:pre-wrap;word-break:break-all">&lt;script src="https://cdntag.tarteaucitron.io/load.js?domain=<strong>your-shop.com</strong>&amp;uuid=<strong>0123456789abcdef0123456789abcdef01234567</strong>"&gt;&lt;/script&gt;</pre>
        </div>
        {if !empty($fields_value.FA_TAC_UUID)}
          </details>
        {/if}
      </div>
    </div>
  {else}
    {$smarty.block.parent}
  {/if}
{/block}

{block name="description"}
  {if $input.name == 'FA_TAC_UUID'}
    <div class="help-block fa-tac-uuid-help">
      <p>{l s='Value of the uuid= parameter of the installation tag (the string of letters and digits after uuid=). No need to type it if you paste the tag in the panel above.' d='Modules.Freshapptarteaucitron.Admin'}</p>
    </div>
  {elseif $input.name == 'FA_TAC_JSCODE'}
    <div class="help-block fa-tac-services-help">
      <p>
        {l s='Only needed if you are not in automatic mode. Find the code of each service here:' d='Modules.Freshapptarteaucitron.Admin'}
        <a href="{$fa_tac_services_url|escape:'html':'UTF-8'}" target="_blank" rel="noopener noreferrer">{$fa_tac_services_url|escape:'html':'UTF-8'}</a>
      </p>
      <p>
        {l s='Example, for YouTube add:' d='Modules.Freshapptarteaucitron.Admin'}
        <code>(tarteaucitron.job = tarteaucitron.job || []).push('youtube');</code>
      </p>
    </div>
  {else}
    {$smarty.block.parent}
  {/if}
{/block}
