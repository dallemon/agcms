function x_getTable()
{
    sajax.doCall('getTable', arguments, "GET", true, "/ajax.php");
    return false;
}

function getAddress(phonenumber, function_name)
{
    phonenumber = phonenumber.replace('/\s/', '');
    phonenumber = phonenumber.replace('/^[+]45/', '');
    if(!phonenumber) {
        alert('De skal udfylde telefon nummeret først.');
        return false;
    }
    if(phonenumber.length != 8) {
        alert('Telefonnummeret skal være på 8 cifre!');
        return false;
    }
    x_getAddress(phonenumber, function_name);
}

function x_getAddress()
{
    sajax.doCall('getAddress', arguments, "GET", true, "/ajax.php");
    return false;
}

function x_getKat()
{
    sajax.doCall('getKat', arguments, "GET", true, "/ajax.php");
    return false;
}

function inject_html(data)
{
    if(data.error || !data) {
        alert(data.error);
        return;
    }

    document.getElementById(data.id).innerHTML = data.html;
}
