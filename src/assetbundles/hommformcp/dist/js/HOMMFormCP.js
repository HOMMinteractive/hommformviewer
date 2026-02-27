/**
 * hommform plugin for Craft CMS
 *
 * Index Field JS
 *
 * @author    Domenik Hofer
 * @copyright Copyright (c) 2019 HOMM interactive
 * @link      https://github.com/HOMMinteractive
 * @package   HOMMForm
 * @since     1.0.0
 */


let $tr = document.querySelectorAll('.hommform__table tbody tr');

document.querySelector('.hommform_search').addEventListener('keyup', function (e) {
    let search = e.target.value.toLocaleLowerCase();

    $tr.forEach(function ($tr) {
        if ($tr.innerHTML.toLocaleLowerCase().includes(search)) {
            $tr.style.display = ''
        } else {
            $tr.style.display = 'none'
        }
    });
});
