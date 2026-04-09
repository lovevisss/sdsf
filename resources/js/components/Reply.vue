<script>
   import Favorite from "./Favorite";
    export default {
        props: ['attributes'],
        components: {Favorite},
        data(){
            return {
                editing: false,
                body: this.attributes.body,
            }
        },
        computed:{

        },
        methods:{

        }
    }

</script>

<template>
    <div id="reply-{{attributes.id}}" class="card mb-3">
        <div class="card-body">
            <div class="level">
                <h5 class="flex">

                </h5>
                <favorite :attributes="attributes"></favorite>
            </div>
        </div>
    </div>
</template>

<style scoped></style>
