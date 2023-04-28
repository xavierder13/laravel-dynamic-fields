<template>
  <v-app>
    <v-main>
      <div class="d-flex justify-content-center full-height error-content">
        <div class="align-self-center">
          <div class="row">
            <div class="code">
              401
            </div>
            <div class="message">
              Unauthorize
            </div>
          </div>
          <div class="row justify-content-center">
            <div class="btn-back-home">
              
              <!-- <router-link :to="{ path: $routerHistory.previous().path }">><i class="fa fa-angle-double-left"></i> Back</router-link> -->
              <!-- <a @click="$router.go(-1)"><i class="fa fa-angle-double-left"></i> Back</a> -->
              <v-btn
                text
                color="blue"
                link
                @click="$router.go(-1)"
              >      
                Back
              </v-btn>
            </div>
            <div class="btn-home ">
              <!-- <router-link to="/">Home Page</router-link> -->
              <v-btn
                text
                color="blue"
                link
                to="/"
              >
                Home Page
              </v-btn>
            </div>
          </div>
        </div>
      </div>
    </v-main>
  </v-app>
</template>

<style>
    .error-content {
      background-color: #fff;
      color: #636b6f;
      font-family: 'Nunito', sans-serif;
      font-weight: 100;
      height: 100vh;
      margin: 0;
    }
    .full-height {
      height: 100vh;
    }

    .flex-center {
      align-items: center;
      display: flex;
      justify-content: center;
    }

    .position-ref {
      position: relative;
    }

    .code {
      border-right: 2px solid;
      font-size: 26px;
      padding: 0 15px 0 15px;
      text-align: center;
    }

    .message {
      font-size: 18px;
      text-align: center;
      padding: 7px 0 0 15px;
    }

    .btn-back-home {
      font-size: 15px;
      padding: 5px 0 0 0;
      text-align: center;
    }
    .btn-home {
      font-size: 15px;
      padding: 5px 0 0 0;
      text-align: center;
    }

  </style>