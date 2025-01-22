{*
* @author LupaSearch
* @copyright LupaSearch
* @license MIT
*}
{if isset($uiPluginConfigurationKey) && $uiPluginConfigurationKey}
  <!-- start of LupaSearch script -->
  <script src="https://cdn.lupasearch.com/client/lupasearch-latest.min.js"></script>
  {if isset($uiPluginOptionOverrides) && $uiPluginOptionOverrides}
  <script>lupaSearch.init("{$uiPluginConfigurationKey}", {}, {$uiPluginOptionOverrides nofilter});</script>
  {else}
  <script>lupaSearch.init("{$uiPluginConfigurationKey}", {});</script>
  {/if}
  <!-- end of LupaSearch script -->
{/if}
