var searchterm = new LiveValidation( 'searchterm', { validMessage: " " } );
searchterm.add( Validate.Format, { pattern: /^[a-z0-9_ ]{1,64}$/, failureMessage: "Invalid search term!" } );
