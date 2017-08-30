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
<div class="row">
    <div class="col-lg-12">
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-dns"></i> {l s='LP Express parcel terminal info' mod='lpexpress'}
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>{l s='Address' mod='lpexpress'}</th>
                            <th>{l s='Collecting hours' mod='lpexpress'}</th>
                            <th>{l s='Comment' mod='lpexpress'}</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{$terminal['terminal_id']|escape:'htmlall':'UTF-8'}</td>
                            <td>{$terminal['name']|escape:'htmlall':'UTF-8'}, {$terminal['address']|escape:'htmlall':'UTF-8'}, {$terminal['zip']|escape:'htmlall':'UTF-8'}, {$terminal['city']|escape:'htmlall':'UTF-8'}</td>
                            <td>{$terminal['collectinghours']|escape:'htmlall':'UTF-8'}</td>
                            <td>{$terminal['comment']|escape:'htmlall':'UTF-8'}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>