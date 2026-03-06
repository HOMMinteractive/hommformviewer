/**
 * hommform plugin for Craft CMS
 *
 * Index Field JS
 *
 * @author    Benjamin Ammann
 * @copyright Copyright (c) 2026 HOMM interactive
 * @link      https://github.com/HOMMinteractive
 * @package   HOMMForm
 * @since     4.0.0
 */


let tableRows = document.querySelectorAll('.hommform__table tbody tr');

document.querySelector('.hommform_search').addEventListener('keyup', function (e) {
    let search = e.target.value.toLocaleLowerCase();

    tableRows.forEach(function ($tr) {
        if ($tr.innerHTML.toLocaleLowerCase().includes(search)) {
            $tr.style.display = ''
        } else {
            $tr.style.display = 'none'
        }
    });
});
