var searchterm = new LiveValidation( 'searchterm', { validMessage: " " } );
searchterm.add( Validate.Format, { pattern: /^[a-z0-9_ ]{1,64}$/, failureMessage: "Invalid search term!" } );

var username = new LiveValidation( 'username', { validMessage: "<" } );
username.add( Validate.Format, { pattern: /^[a-z0-9_]{1,64}$/, failureMessage: "Invalid username!" } );

var password = new LiveValidation( 'password', { validMessage: "<" } );
password.add( Validate.Format, { pattern: /(?=^[!-~]{8,64}$)(?=.*[a-z])(?=.*[A-Z])(?=.*[^A-Za-z0-9])(?=^.*[^\s].*$)(?=.*[\d]).*$/, failureMessage: "Invalid password!" } );
