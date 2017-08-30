{*
Available variables:
    $terminal => array
        $terminal['terminal_id'] => terminal's in lpexpress.lt database. Eg.: 1234
        $terminal['zip'] => zip code of terminal (only numbers). Eg.: 34828
        $terminal['name'] => name of place where terminal is placed. Usually shop or post office. Eg.: Maxima X
        $terminal['city'] => city of terminal. Eg.: Vilnius
        $terminal['address'] => street and no. Eg.: Savanorių 239
        $terminal['comment'] => instructions how to find terminal. LITHUANIAN. Eg.: Šalia įėjimo į parduotuvę.
        $terminal['collectinghours'] => hours when parcel's is picked from terminal. LITHUANIAN. Eg.: I - V iki 20:00, VI iki 15:20
*}
<!-- @TODO: styles to CSS file -->
<div class="row lpexpress_terminals_list" id="lpexpress_terminals_list" style="margin-bottom: .9375rem; padding: .9375rem 0;">
    <div class="col-sm-4">
        <div class="row">
            <div class="col-xs-12">
                <span class="h6 carrier-name">{l s='Choose terminal' mod='lpexpress'}</span>
            </div>
        </div>
    </div>
    <div class="col-sm-8">
        <div class="row">
            <div class="col-xs-12">
                <select name="lpexpress_terminal_id">
                    <option value="0">{l s='-- please choose --' mod='lpexpress'}</option>
                    {foreach from=$terminals key=city item=city_terminals}
                        <optgroup label="{$city|escape:'htmlall':'UTF-8'}">
                        {foreach from=$city_terminals item=terminal}
                            <option value="{$terminal.terminal_id|escape:'htmlall':'UTF-8'}">
                                {$terminal['name']|escape:'htmlall':'UTF-8'}
                                ({$terminal['address']|escape:'htmlall':'UTF-8'})
                            </option>
                        {/foreach}
                        </optgroup>
                    {/foreach}
                </select>
            </div>
        </div>
    </div>
</div>