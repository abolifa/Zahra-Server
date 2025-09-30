<template>
    <CSidebar
        :class="{ 'sidebar-rtl': isRtl }"
        :minimize="minimize"
        :show="show"
        fixed
        @update:show="(value) => $store.commit('set', ['sidebarShow', value])"
    >
        <CSidebarBrand class="d-md-down-none" to="/">
            <CIcon
                :height="35"
                :viewBox="`0 0 ${minimize ? 110 : 556} 134`"
                class="d-block"
                name="logo"
                size="custom-size"
            />
        </CSidebarBrand>
        <CRenderFunction :content-to-render="nav" flat/>
        <CSidebarMinimizer
            class="d-md-down-none"
            @click.native="$store.commit('set', ['sidebarMinimize', !minimize])"
        />
    </CSidebar>
</template>

<script>
import axios from 'axios'
import $store from "lodash/seq";

export default {
    name: 'TheSidebar',
    data() {
        return {
            nav: [],
            buffor: [],
        }
    },
    computed: {
        isRtl() {
            return localStorage.getItem('lang') === 'ar';
        },
        $store() {
            return $store
        },
        show() {
        },
        minimize() {
        }
    },
    methods: {
        dropdown(data) {
            let result = {
                _name: 'CSidebarNavDropdown',
                name: data['name'],
                route: data['href'],
                icon: data['icon'],
                _children: [],
            }
            for (let i = 0; i < data['elements'].length; i++) {
                if (data['elements'][i]['slug'] === 'dropdown') {
                    result._children.push(this.dropdown(data['elements'][i]));
                } else {
                    result._children.push(
                        {
                            _name: 'CSidebarNavItem',
                            name: data['elements'][i]['name'],
                            to: data['elements'][i]['href'],
                            icon: data['elements'][i]['icon']
                        },
                    );
                }
            }
            return result;
        },
        rebuildData(data) {
            this.buffor = [{
                _name: 'CSidebarNav',
                _children: []
            }];
            for (let k = 0; k < data.length; k++) {
                switch (data[k]['slug']) {
                    case 'link':
                        if (data[k]['href'].indexOf('http') !== -1) {
                            this.buffor[0]._children.push(
                                {
                                    _name: 'CSidebarNavItem',
                                    name: data[k]['name'],
                                    href: data[k]['href'],
                                    icon: data[k]['icon'],
                                    target: '_blank'
                                }
                            );
                        } else {
                            this.buffor[0]._children.push(
                                {
                                    _name: 'CSidebarNavItem',
                                    name: data[k]['name'],
                                    to: data[k]['href'],
                                    icon: data[k]['icon'],
                                }
                            );
                        }
                        break;
                    case 'title':
                        this.buffor[0]._children.push(
                            {
                                _name: 'CSidebarNavTitle',
                                _children: [data[k]['name']]
                            }
                        );
                        break;
                    case 'dropdown':
                        this.buffor[0]._children.push(this.dropdown(data[k]));
                        break;
                }
            }
            return this.buffor;
        }
    },
    mounted() {
        console.log('the sidebar mounted');
        this.$root.$on('toggle-sidebar', () => {
            const sidebarOpened = this.show === true || this.show === 'responsive'
            this.show = sidebarOpened ? false : 'responsive'
        })
        this.$root.$on('toggle-sidebar-mobile', () => {
            const sidebarClosed = this.show === 'responsive' || this.show === false
            this.show = sidebarClosed ? true : 'responsive'
        })
        let self = this;
        axios.get(this.$apiAdress + '/api/menu?token=' + localStorage.getItem("api_token"))
            .then(function (response) {
                self.nav = self.rebuildData(response.data);
            }).catch(function (error) {
            console.log(error);
            self.$router.push({path: '/login'});
        });
    }
}
</script>
