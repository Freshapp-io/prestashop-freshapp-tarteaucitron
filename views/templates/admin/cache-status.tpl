{*
 * FreshApp Tarteaucitron
 * @author FreshApp.io
 * @copyright 2026 FreshApp.io
 * @license GPL-3.0-or-later
 *}
{* Icônes : Material Icons, la police du back-office PrestaShop 9. Les classes icon-* de
   FontAwesome ne sont plus toutes définies (icon-cogs, icon-refresh s'affichent vides). *}
{* Les formulaires du module occupent toute la largeur du panneau : PrestaShop 9 limite
   .form-wrapper à 83 % et le centre, ce qui décalait les champs d'un panneau à l'autre. *}
<style>
  .bootstrap .form-horizontal.fa-tac-form .form-wrapper { flex: 0 0 100%; max-width: 100%; margin: 0; padding: 14px 20px; }
</style>
<form method="post" action="{$fa_tac_action_url|escape:'html':'UTF-8'}" class="form-horizontal fa-tac-form">
<div class="panel">
  <div class="panel-heading">
    <i class="material-icons" style="vertical-align:middle;font-size:18px">cloud_download</i>
    {l s='Cache local de load.js' mod='freshapptarteaucitron'}&nbsp;
    {if $fa_tac_state == 'error'}
      <span class="badge badge-danger">{l s='Loader non chargé' mod='freshapptarteaucitron'}</span>
    {elseif $fa_tac_state == 'cdn'}
      <span class="badge badge-default">{l s='Mode CDN' mod='freshapptarteaucitron'}</span>
    {elseif $fa_tac_state == 'invalid'}
      <span class="badge badge-danger">{l s='Cache invalide' mod='freshapptarteaucitron'}</span>
    {elseif $fa_tac_state == 'missing'}
      <span class="badge badge-warning">{l s='Non téléchargé' mod='freshapptarteaucitron'}</span>
    {elseif $fa_tac_state == 'uuid'}
      <span class="badge badge-warning">{l s='UUID modifié' mod='freshapptarteaucitron'}</span>
    {elseif $fa_tac_state == 'expired'}
      <span class="badge badge-warning">{l s='Cache expiré' mod='freshapptarteaucitron'}</span>
    {else}
      <span class="badge badge-success">{l s='Cache actif' mod='freshapptarteaucitron'}</span>
    {/if}
  </div>
  <div class="form-wrapper">
    {if $fa_tac_state == 'error'}
      <p class="text-danger">{l s='Le dernier téléchargement a échoué : le détail est affiché en haut de la page.' mod='freshapptarteaucitron'}</p>
    {elseif $fa_tac_state == 'cdn'}
      <p class="text-muted">{l s='load.js est chargé depuis le CDN tarteaucitron.io à chaque visite.' mod='freshapptarteaucitron'}</p>
    {elseif $fa_tac_state == 'invalid'}
      <p class="text-danger">{l s='Le fichier en cache est trop petit pour être le loader (réponse d\'erreur du CDN mise en cache). Le site est repassé sur le CDN ; relancez le téléchargement pour connaître la cause exacte.' mod='freshapptarteaucitron'}</p>
    {elseif $fa_tac_state == 'missing'}
      <p>{l s='Le fichier cache n\'existe pas encore. Il sera téléchargé automatiquement lors de la prochaine visite du site front.' mod='freshapptarteaucitron'}</p>
    {elseif $fa_tac_state == 'uuid'}
      <p class="text-warning">{l s='L\'UUID a changé depuis le dernier téléchargement. Le fichier sera re-téléchargé automatiquement à la prochaine visite.' mod='freshapptarteaucitron'}</p>
    {elseif $fa_tac_state == 'expired'}
      <p>{l s='Le cache a expiré. Il sera re-téléchargé automatiquement à la prochaine visite.' mod='freshapptarteaucitron'}</p>
    {else}
      <p class="text-success">{l s='load.js est servi en local et intégré aux assets du site. Prochain re-téléchargement le' mod='freshapptarteaucitron'} <strong>{$fa_tac_expiry|escape:'html':'UTF-8'}</strong>.</p>
    {/if}

    {if $fa_tac_last_download}
      <p>{l s='Dernier téléchargement' mod='freshapptarteaucitron'}&nbsp;: <strong>{$fa_tac_last_download|escape:'html':'UTF-8'}</strong></p>
    {/if}
    {if $fa_tac_file_kb !== null}
      <p>{l s='Taille du fichier cache' mod='freshapptarteaucitron'}&nbsp;: <strong>{$fa_tac_file_kb|escape:'html':'UTF-8'} ko</strong></p>
    {/if}

    {* Réglages du cache (anciennement onglet « Cache local » du formulaire Paramètres) *}
    <div style="margin-top:16px">
      <div class="form-group">
        <label class="control-label col-lg-3">{l s='Activer le cache local de load.js' mod='freshapptarteaucitron'}</label>
        <div class="col-lg-9">
          <span class="switch prestashop-switch fixed-width-lg">
            <input type="radio" name="FA_TAC_LOCAL_ENABLED" id="FA_TAC_LOCAL_ENABLED_on" value="1"{if $fa_tac_enabled} checked="checked"{/if}>
            <label for="FA_TAC_LOCAL_ENABLED_on">{l s='Activé' mod='freshapptarteaucitron'}</label>
            <input type="radio" name="FA_TAC_LOCAL_ENABLED" id="FA_TAC_LOCAL_ENABLED_off" value="0"{if !$fa_tac_enabled} checked="checked"{/if}>
            <label for="FA_TAC_LOCAL_ENABLED_off">{l s='Désactivé' mod='freshapptarteaucitron'}</label>
            <a class="slide-button btn"></a>
          </span>
          <p class="help-block">{l s='Télécharge load.js en local et le sert avec les assets du site (minifié) : chargement plus rapide qu\'un appel au CDN à chaque visite. Fallback CDN automatique si le téléchargement échoue.' mod='freshapptarteaucitron'}</p>
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-3" for="FA_TAC_LOCAL_TTL">{l s='Durée du cache' mod='freshapptarteaucitron'}</label>
        <div class="col-lg-9">
          <div class="input-group" style="max-width:12rem">
            <input type="number" min="1" name="FA_TAC_LOCAL_TTL" id="FA_TAC_LOCAL_TTL" value="{$fa_tac_ttl|intval}" class="form-control">
            <span class="input-group-addon">{l s='jours' mod='freshapptarteaucitron'}</span>
          </div>
          <p class="help-block">{l s='Nombre de jours avant re-téléchargement automatique depuis le CDN. Minimum 1.' mod='freshapptarteaucitron'}</p>
        </div>
      </div>
    </div>
  </div>
  <div class="panel-footer">
        <button type="submit" name="uwtac_cache_submit" value="1" class="btn btn-primary pull-right">
          <i class="process-icon-save"></i> {l s='Enregistrer' mod='freshapptarteaucitron'}
        </button>
        {if $fa_tac_enabled}
          <button type="submit" name="uwtac_force_reload" value="1" class="btn btn-default">
            <i class="material-icons" style="vertical-align:middle;font-size:18px">refresh</i>
            {l s='Forcer le re-téléchargement maintenant' mod='freshapptarteaucitron'}
          </button>
        {/if}
  </div>
</div>
</form>
