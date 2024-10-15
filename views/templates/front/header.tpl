{*
* @author LupaSearch
* @copyright LupaSearch
* @license MIT
*}
{if isset($uiPluginConfigurationKey) && uiPluginConfigurationKey}
  <!-- start of LupaSearch script -->
  <script src="https://cdn.lupasearch.com/client/lupasearch-latest.min.js"></script>
  <script>lupaSearch.init("{$uiPluginConfigurationKey}", {});</script>
  <!-- end of LupaSearch script -->
{/if}