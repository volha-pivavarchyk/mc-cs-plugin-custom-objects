Mautic.initializeFormFieldVisibilitySwitcher = function (formName)
{
    Mautic.switchFormFieldVisibilty(formName);

    mQuery('form[name="'+formName+'"]').on('change', function() {
        Mautic.switchFormFieldVisibilty(formName);
    });

    mQuery('form[name="'+formName+'"]').on('change', function() {
        const mappedObject = mQuery('select[name="formfield[mappedObject]"]').find(":selected").val();
        const addField = mQuery('select[name="formfield[properties][saveRemove]"');

        if (mappedObject === 'contact' || mappedObject === 'company') {
            addField.parent().parent().css('display','none');
        }
    });
};