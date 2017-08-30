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
<span style="color:#333"><strong>{l s='LP Express terminal:' mod='lpexpress'}</strong></span> {$terminal['name']|escape:'htmlall':'UTF-8'}, {$terminal['address']|escape:'htmlall':'UTF-8'}, {$terminal['city']|escape:'htmlall':'UTF-8'}<br /><br />