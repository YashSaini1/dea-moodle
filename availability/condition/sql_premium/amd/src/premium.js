define(['jquery'], ($) => {
    return {
        init: (cm, str_locked) => {
            $('#module-'+cm).addClass('locked upgrade_plan');
            $('#module-'+cm+' .sql-disabled').removeClass('sql-disabled').addClass('locked upgrade_plan').html(str_locked);
        },
    };
});