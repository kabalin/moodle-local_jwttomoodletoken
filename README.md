# Production deployment

1. Copy into `local/jwttomoodletoken` directory.

2. Create a role with the `local/jwttomoodletoken:usews` capability.

3. Create a user and assign it this role in the system context.

4. In Moodle's admimnistration, chose Web Services > Manage Tokens, and create a token for this user – if needed use IP address restriction.

5. Configure jwttomoodletoken plugin. Here you can specify public key
   or JSON Web Key Sets URI. If URL is provided, public key is not used for
   decoding.

# Requests

Try for instance this to request a mobile token for a given access token:

```
https://your.moodle/webservice/rest/server.php?wstoken=<YOUR_TOKEN>>&wsfunction=local_jwttomoodletoken_gettoken&accesstoken=
<ACCESS_TOKEN>&moodlewsrestformat=json
```
