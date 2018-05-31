/**
 * Created by tuhin on 17/5/17.
 */

function replaceAllByMappedObj(str, mapObj) {
    var re = new RegExp(Object.keys(mapObj).join("|"),"gi");

    return str.replace(re, function(matched){
        return mapObj[matched.toLowerCase()];
    });
}

/**
 * Convert a string/number/float to float with precision 2
 *
 * @param x
 * @returns {Number}
 */
function parseFloat2(x) {
    return parseFloat(parseFloat(Math.round(x * 100) / 100).toFixed(2));
}