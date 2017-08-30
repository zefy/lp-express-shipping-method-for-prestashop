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
<div class="box">
    <h3>{l s='Delivery to LP Express parcel terminal' mod='lpexpress'}</h3>
    <table class="table table-striped table-bordered hidden-sm-down">
        <thead class="thead-default">
        <tr>
            <th>{l s='Address' mod='lpexpress'}</th>
            <th>{l s='Comment' mod='lpexpress'}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>{$terminal['name']|escape:'htmlall':'UTF-8'}, {$terminal['address']|escape:'htmlall':'UTF-8'}, {$terminal['city']|escape:'htmlall':'UTF-8'}</td>
            <td>{$terminal['comment']|escape:'htmlall':'UTF-8'}</td>
        </tr>
        </tbody>
    </table>
    <div class="hidden-md-up shipping-lines">
        <div class="shipping-line">
            <ul>
                <li>
                    <strong>{l s='Address' mod='lpexpress'}</strong> {$terminal['name']|escape:'htmlall':'UTF-8'}, {$terminal['address']|escape:'htmlall':'UTF-8'}
                </li>
                <li>
                    <strong>{l s='Comment' mod='lpexpress'}</strong> {$terminal['comment']|escape:'htmlall':'UTF-8'}
                </li>
            </ul>
        </div>
    </div>
</div>