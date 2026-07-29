{*
* @author LupaSearch
* @copyright LupaSearch
* @license MIT
*}
{if isset($uiPluginConfigurationKey) && $uiPluginConfigurationKey}
  <!-- start of LupaSearch script -->
  <script src="https://cdn.lupasearch.com/client/lupasearch-latest.min.js"></script>
  {if isset($uiPluginOptionOverridesUrl) && $uiPluginOptionOverridesUrl}
  <script src="{$uiPluginOptionOverridesUrl|escape:'html':'UTF-8'}"></script>
  <script>
    lupaSearch.init(
      "{$uiPluginConfigurationKey|escape:'javascript':'UTF-8'}",
      {},
      window.__lupaSearchOptionOverrides || {}
    );
  </script>
  {else}
  <script>lupaSearch.init("{$uiPluginConfigurationKey|escape:'javascript':'UTF-8'}", {});</script>
  {/if}
  <!-- end of LupaSearch script -->
{/if}
