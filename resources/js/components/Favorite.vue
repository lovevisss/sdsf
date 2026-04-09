<script >
    export default {
        data(){
            return {
                isFavorited: this.attributes.isFavorited,
                favoritesCount: this.attributes.favoritesCount
            }
        },
        computed:{
            favoriteClasses(){
                return ['btn', 'btn-toggle', this.isFavorited ? 'btn-primary' : 'btn-default'];
            }
        },
        methods:{
            toggleFavorite(){
                return this.isFavorited ? this.unfavorite() : this.favorite();
            },
            favorite(){
                axios.post('/replies/' + this.attributes.id + '/favorites');
                this.favoritesCount++;
                this.isFavorited = ! this.isFavorited;

                flash('Favorited');
            },
            unfavorite(){
                axios.delete('/replies/' + this.attributes.id + '/favorites');
                this.favoritesCount--;
                this.isFavorited = ! this.isFavorited;

                flash('Unfavorited');
            }
        }
    }
</script>

<template>
    <button type="button" :class="favoriteClasses" @click="toggleFavorite">
        <span class="glyphicon glyphicon-heart"></span>
        <span v-text="favoritesCount" v-show="favoritesCount"></span>
    </button>
</template>

<style scoped></style>
