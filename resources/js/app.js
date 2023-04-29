import Vue from 'vue';
import Vuetify from '../plugins/vuetify';
import VuetifyMask from '../plugins/vuetify-mask';
import App from './App.vue';
import Vuelidate from 'vuelidate';
import router from './router';
import VueSweetalert2 from 'vue-sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
// import VueSocketio from 'vue-socket.io';
import store from './store';
import IdleVue from "idle-vue";

// Vue.use(VueSocketio, 'http://localhost:4000');
Vue.use(Vuetify);   
Vue.use(VuetifyMask);
Vue.use(Vuelidate);
Vue.use(VueSweetalert2);
Vue.use(IdleVue, {
  eventEmitter: new Vue(),
  store,
  idleTime: 1800000, // 30 mins
  startAtIdle: false
});

const app = new Vue({
    vuetify: Vuetify,
    el: '#app',
    router,
    store,
    render: h => h(App),
});