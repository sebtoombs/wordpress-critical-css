/*(function($) {
    $(document).ready(initAdminForm);

    var formSelector = '.critical-css-admin form'

    function initAdminForm() {
        $(formSelector).on('submit', function(e) {
            e.preventDefault();

            var $fields = getFormFields();
            var values = getFieldValues($fields);

            saveOptions(values)
        })

        $(formSelector + ' [data-validate-api-key]').on('click', function(e) {
            console.log('Validating...');
            e.preventDefault();
            var $field = $(formSelector + ' #api_key');
            if(!$field || !$field.get(0)) {
                return;
            }
            var key = $field.val();
            validateApiKey(key)
        })

        function getFormFields() {
            var fields = ['ignore_styles']
            var $fields = {};
            fields.map(function(field) {
                var $field = $(formSelector + ' #'+field)
                if($field && $field.get(0)){
                    $fields[field] = $field;
                } else {
                    $fields[field] = null;
                }
            })
            return $fields;
        }

        function getFieldValues($fields) {
            var fieldValues = {}
            for(var key of Object.keys($fields)) {
                if($fields.hasOwnProperty(key)) {
                    if($fields[key]) {
                        fieldValues[key] = $fields[key].val()
                    } else {
                        fieldValues[key] = null;
                    }
                }
            }
            return fieldValues;
        }


        function saveOptions(options, cb) {
            $.ajax({
                url: ajaxurl,
                method: 'post',
                data: {
                    action:'admin_update_option',
                    options:options,
                    nonce:window.critical_css_admin.nonce
                }
            }).then(function(resp) {
                console.log(resp)
            }, function(err) {
                console.log(err)
            })
        }

        function validateApiKey(key, cb) {
            $.ajax({
                url: ajaxurl,
                method: 'post',
                data: {
                    action:'admin_validate_api_key',
                    key:key,
                    nonce:window.critical_css_admin.nonce
                }
            }).then(function(resp) {
                console.log('Success', resp)
            }, function(err) {
                console.log('Error', err.responseJSON)
            })
        }
    }
})(jQuery);*/


