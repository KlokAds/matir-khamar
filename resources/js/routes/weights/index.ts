import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\HerdController::store
* @see app/Http/Controllers/HerdController.php:133
* @route '/animals/{animal}/weights'
*/
export const store = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/animals/{animal}/weights',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\HerdController::store
* @see app/Http/Controllers/HerdController.php:133
* @route '/animals/{animal}/weights'
*/
store.url = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { animal: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { animal: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            animal: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        animal: typeof args.animal === 'object'
        ? args.animal.id
        : args.animal,
    }

    return store.definition.url
            .replace('{animal}', parsedArgs.animal.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\HerdController::store
* @see app/Http/Controllers/HerdController.php:133
* @route '/animals/{animal}/weights'
*/
store.post = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\HerdController::store
* @see app/Http/Controllers/HerdController.php:133
* @route '/animals/{animal}/weights'
*/
const storeForm = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\HerdController::store
* @see app/Http/Controllers/HerdController.php:133
* @route '/animals/{animal}/weights'
*/
storeForm.post = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

store.form = storeForm

const weights = {
    store: Object.assign(store, store),
}

export default weights