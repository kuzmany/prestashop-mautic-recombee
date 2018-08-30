{if $error}
    <h4 style="color:red">{l s='Api Credentials are not valid. Please check it.' mod='mauticprestashop'}</h4>
{elseif !$has_data}
    <h4>{l s='Please fill API Credentials, then will allow to continue authorize your app.' mod='mauticprestashop'}</h4>
{else}
    {if $access_token_data}
        {if $validAccessToken}
            <h4 style="color:green">{l s='Access tokens are valid.' mod='mauticprestashop'}</h4>
        {else}
            <h4 class="color:red">{l s='Access token data are not valid.' mod='mauticprestashop'}</h4>
            <a href="{$auth_url|escape:'htmlall':'UTF-8'}"><button  type="button" class="btn btn-default  btn-warning">{l s='Let\'s authorize your app (OAuth 1)' mod='mauticprestashop'}</button></a>
            {/if}
        {else}
        <h4 class="color:red">{l s='Access token data are not valid.' mod='mauticprestashop'}</h4>
        <a href="{$auth_url|escape:'htmlall':'UTF-8'}"><button  type="button" class="btn btn-default btn-warning">{l s='Let\'s authorize your app (OAuth 1)' mod='mauticprestashop'}</button></a>
    {/if}
{/if}