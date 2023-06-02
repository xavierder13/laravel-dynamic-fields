
import axios from 'axios';
import router from '../../router';

const state = {
  permissions: [],
  roles: [],
  userRolesPermissionsIsLoaded: false,
};

const getters = {
  hasRole: (state) => (role) => {
    let hasRole = true;

    if(Array.isArray(role))
    {
      hasRole = role.every(value => state.roles.includes(value))
    }
    else
    {
      hasRole = state.permissions.includes(role);
    }
    return hasRole;

  },
  hasAnyRole: (state) => (role) => {
    let hasRole = false;
    
    if(Array.isArray(role))
    {
      role.forEach(value => {
        if(state.roles.includes(value))
        {
          hasRole = true;
        }
      });
    }
    
    return hasRole;
  },
  hasPermission: (state) => (permission) => {
    let hasPermission = true;

    if(Array.isArray(permission))
    {
      hasPermission = permission.every(value => state.permissions.includes(value))
    }
    else
    {
      hasPermission = state.permissions.includes(permission);
    }
    return hasPermission;
  },
  hasAnyPermission: (state) => (permission) => {
    let hasPermission = false;
    
    if(Array.isArray(permission))
    {
      permission.forEach(value => {
        if(state.permissions.includes(value))
        {
          hasPermission = true;
        }
      });
    }
    
    return hasPermission;
  }
};

const actions = {
  async userRolesPermissions({ commit }) {
    let response = await axios.get("/api/user/roles_permissions").then((response) => {
      commit('setUserRoles', response.data.user_roles);
      commit('setUserPermissions', response.data.user_permissions);

    },
      (error) => {
        if (error.response.status == "401") {
          router.push({ name: "unauthorize" });
        }
      });

  },

};

const mutations = {
  setUserRoles(state, roles) {
    state.roles = roles;
  },
  setUserPermissions(state, permissions) {
    state.permissions = permissions;
    // set true if user roles and permissions value successfully assigned
    state.userRolesPermissionsIsLoaded = true;
  },

};

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations,
}
