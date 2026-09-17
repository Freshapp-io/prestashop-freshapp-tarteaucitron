{*
 * FreshApp Tarteaucitron
 * @author FreshApp.io
 * @copyright 2026 FreshApp.io
 * @license Proprietary - see LICENSE file
 *}
{* Erreur du dernier téléchargement de load.js, affichée en tête de page : le bandeau de
   consentement ne s'affiche pas tant qu'elle n'est pas corrigée. *}
<div class="alert alert-danger fa-tac-loader-error">
  <p><strong>{l s='Le gestionnaire de consentement n\'est pas actif.' mod='freshapptarteaucitron'}</strong></p>
  <p style="word-break:break-word">{$fa_tac_last_error|escape:'html':'UTF-8'}</p>
  <p>{l s='Le site est repassé sur le CDN en attendant. Corrigez le point ci-dessus puis relancez le téléchargement depuis le panneau « Cache local de load.js ».' mod='freshapptarteaucitron'}</p>
</div>
