var password = new LiveValidation( 'password', { validMessage: "<" } );
password.add( Validate.Format, { pattern: /(?=^[!-~]{8,64}$)(?=.*[a-z])(?=.*[A-Z])(?=.*[^A-Za-z0-9])(?=^.*[^\s].*$)(?=.*[\d]).*$/, failureMessage: "Invalid password!" } );

var password2 = new LiveValidation( 'password2', { validMessage: "<" } );
password2.add( Validate.Confirmation, { match: 'password', failureMessage: "Passwords don't match!" } );
