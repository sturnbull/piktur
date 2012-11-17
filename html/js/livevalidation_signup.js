var searchterm = new LiveValidation( 'searchterm', { validMessage: " " } );
searchterm.add( Validate.Format, { pattern: /^[a-z0-9_ ]{1,64}$/, failureMessage: "Invalid search term!" } );

var username = new LiveValidation( 'username', { validMessage: "<" } );
username.add( Validate.Format, { pattern: /^[a-z0-9_]{1,64}$/, failureMessage: "Invalid username!" } );

var email = new LiveValidation( 'email', { validMessage: "<" } );
email.add( Validate.Email, { failureMessage: "Invalid email address!" } );
email.add( Validate.Format, { pattern: /^[a-z0-9_@\.]{5,64}$/, failureMessage: "Invalid email address!" } );

var email2 = new LiveValidation( 'email2', { validMessage: "<" } );
email2.add( Validate.Confirmation, { match: 'email', failureMessage: "Email addresses don't match!" } );

var password = new LiveValidation( 'password', { validMessage: "<" } );
password.add( Validate.Format, { pattern: /(?=^[!-~]{8,64}$)(?=.*[a-z])(?=.*[A-Z])(?=.*[^A-Za-z0-9])(?=^.*[^\s].*$)(?=.*[\d]).*$/, failureMessage: "Invalid password!" } );

var password2 = new LiveValidation( 'password2', { validMessage: "<" } );
password2.add( Validate.Confirmation, { match: 'password', failureMessage: "Passwords don't match!" } );

var oldpassword = new LiveValidation( 'oldpassword', { validMessage: "<" } );
oldpassword.add( Validate.Format, { pattern: /(?=^[!-~]{8,64}$)(?=.*[a-z])(?=.*[A-Z])(?=.*[^A-Za-z0-9])(?=^.*[^\s].*$)(?=.*[\d]).*$/, failureMessage: "Invalid password!" } );

