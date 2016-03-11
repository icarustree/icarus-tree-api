/**
 * Generates a passkey and puts it in the given element.
 * 
 * @param {string} element
 */
function generateKey(element){
    
    var length = 30;
    
    var chars = 'abcdefghijklmnopqrstuvwxyz';
    chars += 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    chars += '0123456789';
    chars += '~!@$%^&*()_+-={}[]:;\<>?,./|\\';
    
    var result = '';
    for (var i = length; i > 0; --i) {
        result += chars[Math.floor(Math.random() * chars.length)];
    }
    
    jQuery(element).val(result);
    
}
