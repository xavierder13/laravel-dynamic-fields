# Laravel + Vue.js with Socket.io
A template for starter projects.

### Here are the Steps to follow in order to run this project:
<ul>
  <li><b>Clone this project through:</b></li>
    <ul>
      <li>HTTPS: <code>https://github.com/monielly/laravel-socket-io.git</code></li>
      <li>SSH: <code>git@github.com:monielly/laravel-socket-io.git</code></li>
      <li>Github CLI: <code>gh repo clone monielly/laravel-socket-io</code></li>
    </ul>
  </li>
  <li><b>Install PHP dependencies: <code>$ composer install</code></b></li> 
  <li><b>Create a blank Database in your backend server.</b></li>
  <li><b>Create .env file from .env.example: <code>$ sudo cp .env.example .env</code></b></li>
  <li><b>Generate key by running this command:</b> <code>php artisan key:generate</code></li>
  <li><b>Generate passport key for api by running this command:</b> <code>php artisan passport:install</code></li>
  <li><b>Migrate the db-migrations by running this command:</b> <code>php artisan migrate</code></li>
  <li><b>Run the db-seeder with this command:</b>
    <ul>
      <li><code>php artisan db:seed</code></li> or 
      <li><code>php artisan db:seed --class=CreateAdminUserSeeder</code></li> and 
      <li><code>php artisan db:seed --class=PermissionSeeder</code></li>
    </ul>
  </li>
  <li><b>Required npm commands:</b></li>
    <ul>
      <li><code>sudo npm install --global cross-env</code></li>
      <li><code>rm -rf node_modules && npm install</code></li>
      <li>then lastly, <code>npm run watch</code></li>
    </ul>
  <li><b>For Socket I.O</b></li>
  <ul>
     <li>Locate to js files by running this command:</li>
      <ul>
        <li><code>cd resources/js</code></li>
        <li>then run, <code>node server.js</code></li>
      </ul>
  </ul>
  <li><b>For Php Artisan commands:</b></li>
    <ul>
      <li><code>php artisan serve</code></li>
      <li>then copy the created URL and port on browser. For example: <code>http://127.0.0.1:8000</code></li>
    <ul>
  <li>To login on page, see sample account that were created awhile ago on Seeders.</li>
</ul>
