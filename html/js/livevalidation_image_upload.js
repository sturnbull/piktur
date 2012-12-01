var description = new LiveValidation( 'description', { validMessage: "<" } );
description.add( Validate.Format, { pattern: /^[a-zA-Z0-9_ ,]{1,255}$/, failureMessage: "Invalid descrption!" } );

var tags = new LiveValidation( 'tags', { validMessage: "<" } );
tags.add( Validate.Format, { pattern: /^[a-zA-Z0-9_ ,]{1,255}$/, failureMessage: "Invalid tag!" } );
