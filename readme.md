# Icarus Tree API
A simple API plugin for WordPress, that allows for logging in, registering 
users and inserting posts.

When you activate this plugin, it will add a new endpoint to your site URL:
**/icarus/api**

Below you'll find a description of all the available endpoints. You can 
customize various options on the **Icarus Tree API** options page via the admin 
dashboard.

## Login
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
        "user_role": "",
        "user_registered": "2016-02-29 23:24:56",
        "display_name": "Joe",
        "token": "109cc9bdabbb562f0cff",
        "session": "2016-03-09 21:12:40"
    }

## Register
    icarus/api/register
This registers a user on the site.

**POST**

    {
        "api_key": YOUR KEY,
        "username": "JoeBloggs",
        "password": "test1234",
        "email": "joe@notrealemail.com"
    }

**RESPONSE**

    {
        "ID": 1,
        "user_login": "joebloggs",
        "user_nicename": "joebloggs",
        "user_email": "joe@notrealemail.com",
        "user_url": "",
        "user_role": "",
        "user_registered": "2016-02-29 23:24:56",
        "display_name": "Joe",
        "token": "109cc9bdabbb562f0cff",
        "session": "2016-03-09 21:12:40"
    }

## Post
    icarus/api/post
This inserts a post on the site. It requires a token and user ID. This method
is restricted to users with a 'subscriber' role, and some optional values will
be ignored depending on the user's role.

**POST**

    {
        "api_key": YOUR_KEY,
        "token": YOUR_TOKEN,
        "user_id": YOUR_USER_ID,
        "post_title": "My Test Post!",
        "post_content": "Blah blah blah blah"
    }

**OPTIONAL**
    
    {
        "post_content_filtered": "",
        "post_excerpt": "blah",
        "post_status": "publish",
        "post_type": "post",
        "post_date": "Y-m-d h:i:s",
        "post_password": "",
        "post_name": "my_test_post",
        "post_parent": 0,
        "menu_order": 0,
        "tax_input": array,
        "meta_input": array
    }

**RESPONSE**

    {
        "post_id": POST_ID,
    }
