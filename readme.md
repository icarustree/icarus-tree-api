# Icarus Tree API
A simple API plugin for WordPress, that allows for logging in, registering users and inserting posts.

When you activate this plugin, it will add a new endpoint to your site URL:
**/icarus/api**

Below you'll find a description of all the available endpoints. You can customize various options on the **Icarus Tree API** options page via the admin dashboard.

## Login ##
    icarus/api/login
This returns a token when valid credentials for a user is submitted with JSON.

**POST**

    {
		"api_key": YOUR KEY,
		"username": "JoeBloggs",
		"password": "test1234"
	}

**RESPONSE**

    {
		"ID": 1,
		"user_login": "joebloggs",
		"user_nicename": "joebloggs",
		"user_email": "joe@notrealemail.com",
		"user_url": "",
		"user_registered": "2016-02-29 23:24:56",
		"display_name": "Joe",
		"token": "109cc9bdabbb562f0cff",
		"session": "2016-03-09 21:12:40"
	}

