/**
 * hommformviewer plugin for Craft CMS
 *
 * Index Field JS
 *
 * @author    Domenik Hofer
 * @copyright Copyright (c) 2019 HOMM interactive
 * @link      https://github.com/HOMMinteractive
 * @package   HOMMFormViewer
 * @since     1.0.0
 */


let $tr = document.querySelectorAll('.hommformviewer__table tbody tr');

document.querySelector('.hommformviewer_search').addEventListener('keyup', function (e) {
    let search = e.target.value;

    $tr.forEach(function ($tr) {
        if ($tr.innerHTML.includes(search)) {
            $tr.style.display = ''
        } else {
            $tr.style.display = 'none'
        }
    });
});
