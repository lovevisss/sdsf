<script lang="ts">
    import axios from 'axios';

    export default {

        name: 'Ad',

        data(){
            return {
                ad:{
                    companyTagline: "",
                    description:"",
                    image:"",
                    pixel: false,
                    statlink:'',
                },
                loaded: false,
            }
        },
        created(){
            axios.get('https://srv.buysellads.com/ads/CVADC53U.json')
            .then((response)=>{
                console.log(response.data.ads[0]);
                this.ad = response.data.ads[0];
                this.loaded = true;
            })
            .catch((error)=>{
                console.log(error);
            });
        }
    }
</script>

<template>
    <div class="ad" v-if="loaded">
        <div class="container">
            <div class="flex justify-items-center justify-center">

                <img :src="ad.image" :alt="ad.companyTagline" class="ad-thumbnail mr-2">
                <img :src="ad.pixel" v-if="ad.pixel">
                <p class="ad-description">
                    <a :href="ad.statlink">
                        <strong v-text="ad.companyTagline" class="text-white"></strong>
                        <span v-text="ad.description"></span>
                    </a>
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
    .ad{
        background-color: gray;
        color: wheat;
        padding: 1em 0;
    }

    .ad-description > a{
        color:inherit;
    }

    .ad-thumbnail{
        width: 30px;
        height: 30px;
    }
</style>
