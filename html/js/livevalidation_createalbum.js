var searchterm = new LiveValidation( 'searchterm', { validMessage: " " } );
searchterm.add( Validate.Format, { pattern: /^[a-z0-9_ ]{1,64}$/, failureMessage: "Invalid search term!" } );

var albumname = new LiveValidation( 'albumname', { validMessage: "<" } );
albumname.add( Validate.Format, { pattern: /^[a-z0-9_]{1,64}$/, failureMessage: "Invalid Album Name!" } );

var albumdescription = new LiveValidation( 'albumdescription', { validMessage: "<" } );
albumdescription.add( Validate.Format, { pattern: /^[a-z0-9 _]{1,64}$/, failureMessage: "Invalid Description!" } );

