
## This is the instruction for reading and using Project Human School ##

This is a website project that Qiaoying Ma created for ARTF 2223 Experience and Interaction, Summer 2 Semester, 2023.

This is a PHP project built on an open-source PHP framework called Trongate and operates on a connection to Open Ai Company through API KEY. It also requires Apache and MySQL database for full operation.

 
In order to see and interact with the working website human school, these prerequisites need to be fulfilled.

1. Connect to a server. I used XAMPP as a local server. It is very easy to use and runs closely
I will provide an instruction using XAMPP to demonstrate the whole content of this project 
2. Enable Apache and MySQL database
3. update the API KEY in the config/config.php with your onw key.

***
Using Xampp for this website
1. move the entire folder where you keep your Xampp project 
(for example xampp/htdocs/)
[XAMPP is available for download here] https://www.apachefriends.org/
1. Open the XAMPP control panel, enable Apache and MySQL

2. obtain API KEY in the open AI company
[a youtube video for acquiring the key] https://www.youtube.com/watch?v=aVog4J6nIAU&ab_channel=Tom%27sTechAcademy
3. go to [config.php] (/config/config.php)
in the last line, where it says define('API_KEY', 'paste your key here')
paste the  key inside of the single quote of the second parameter.
save and quit the file
4. open a web browser, go to localhost/human_school/, you should have a working website


other feature:
localhost/human_school/posts/manager, allow you manually edited, create, access, and delete a "post", which includes the inputs and content that show on the screen

Known bugs:<br>
1. in [recognition.php]/modules/Recognition/controllers/recoginition.php
 and [Post.php] modules/posts/controllers/Post.php <br>
Entering only one input in recognition page will not break the website, it will still work. However, it will be consider a behavior page action in fetch_new_tutorial(Post.php), which results in outputting a behavior page tutorial in the recognition page.
2. Picture of each post does not get update into database

For anyone who acquires this project, feel free to modify, copy and use codes to your liking. The Trongate uses MIT license which allows you to do the same. I include the license from Trongate in the folder.
