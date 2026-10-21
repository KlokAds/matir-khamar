import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\AnimalController::index
* @see app/Http/Controllers/AnimalController.php:31
* @route '/animals'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/animals',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AnimalController::index
* @see app/Http/Controllers/AnimalController.php:31
* @route '/animals'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AnimalController::index
* @see app/Http/Controllers/AnimalController.php:31
* @route '/animals'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AnimalController::index
* @see app/Http/Controllers/AnimalController.php:31
* @route '/animals'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AnimalController::index
* @see app/Http/Controllers/AnimalController.php:31
* @route '/animals'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AnimalController::index
* @see app/Http/Controllers/AnimalController.php:31
* @route '/animals'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AnimalController::index
* @see app/Http/Controllers/AnimalController.php:31
* @route '/animals'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\AnimalController::show
* @see app/Http/Controllers/AnimalController.php:179
* @route '/animals/{animal}'
*/
export const show = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/animals/{animal}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AnimalController::show
* @see app/Http/Controllers/AnimalController.php:179
* @route '/animals/{animal}'
*/
show.url = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return show.definition.url
            .replace('{animal}', parsedArgs.animal.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AnimalController::show
* @see app/Http/Controllers/AnimalController.php:179
* @route '/animals/{animal}'
*/
show.get = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AnimalController::show
* @see app/Http/Controllers/AnimalController.php:179
* @route '/animals/{animal}'
*/
show.head = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AnimalController::show
* @see app/Http/Controllers/AnimalController.php:179
* @route '/animals/{animal}'
*/
const showForm = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AnimalController::show
* @see app/Http/Controllers/AnimalController.php:179
* @route '/animals/{animal}'
*/
showForm.get = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AnimalController::show
* @see app/Http/Controllers/AnimalController.php:179
* @route '/animals/{animal}'
*/
showForm.head = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\AnimalController::create
* @see app/Http/Controllers/AnimalController.php:52
* @route '/animals/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/animals/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AnimalController::create
* @see app/Http/Controllers/AnimalController.php:52
* @route '/animals/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AnimalController::create
* @see app/Http/Controllers/AnimalController.php:52
* @route '/animals/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AnimalController::create
* @see app/Http/Controllers/AnimalController.php:52
* @route '/animals/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AnimalController::create
* @see app/Http/Controllers/AnimalController.php:52
* @route '/animals/create'
*/
const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AnimalController::create
* @see app/Http/Controllers/AnimalController.php:52
* @route '/animals/create'
*/
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AnimalController::create
* @see app/Http/Controllers/AnimalController.php:52
* @route '/animals/create'
*/
createForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

create.form = createForm

/**
* @see \App\Http\Controllers\AnimalController::store
* @see app/Http/Controllers/AnimalController.php:70
* @route '/animals'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/animals',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AnimalController::store
* @see app/Http/Controllers/AnimalController.php:70
* @route '/animals'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AnimalController::store
* @see app/Http/Controllers/AnimalController.php:70
* @route '/animals'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AnimalController::store
* @see app/Http/Controllers/AnimalController.php:70
* @route '/animals'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AnimalController::store
* @see app/Http/Controllers/AnimalController.php:70
* @route '/animals'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\AnimalController::transfer
* @see app/Http/Controllers/AnimalController.php:237
* @route '/animals/{animal}/transfer'
*/
export const transfer = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: transfer.url(args, options),
    method: 'post',
})

transfer.definition = {
    methods: ["post"],
    url: '/animals/{animal}/transfer',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AnimalController::transfer
* @see app/Http/Controllers/AnimalController.php:237
* @route '/animals/{animal}/transfer'
*/
transfer.url = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return transfer.definition.url
            .replace('{animal}', parsedArgs.animal.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AnimalController::transfer
* @see app/Http/Controllers/AnimalController.php:237
* @route '/animals/{animal}/transfer'
*/
transfer.post = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: transfer.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AnimalController::transfer
* @see app/Http/Controllers/AnimalController.php:237
* @route '/animals/{animal}/transfer'
*/
const transferForm = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: transfer.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AnimalController::transfer
* @see app/Http/Controllers/AnimalController.php:237
* @route '/animals/{animal}/transfer'
*/
transferForm.post = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: transfer.url(args, options),
    method: 'post',
})

transfer.form = transferForm

/**
* @see \App\Http\Controllers\AnimalController::sell
* @see app/Http/Controllers/AnimalController.php:251
* @route '/animals/{animal}/sell'
*/
export const sell = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: sell.url(args, options),
    method: 'post',
})

sell.definition = {
    methods: ["post"],
    url: '/animals/{animal}/sell',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AnimalController::sell
* @see app/Http/Controllers/AnimalController.php:251
* @route '/animals/{animal}/sell'
*/
sell.url = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return sell.definition.url
            .replace('{animal}', parsedArgs.animal.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AnimalController::sell
* @see app/Http/Controllers/AnimalController.php:251
* @route '/animals/{animal}/sell'
*/
sell.post = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: sell.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AnimalController::sell
* @see app/Http/Controllers/AnimalController.php:251
* @route '/animals/{animal}/sell'
*/
const sellForm = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: sell.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AnimalController::sell
* @see app/Http/Controllers/AnimalController.php:251
* @route '/animals/{animal}/sell'
*/
sellForm.post = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: sell.url(args, options),
    method: 'post',
})

sell.form = sellForm

/**
* @see \App\Http\Controllers\AnimalController::death
* @see app/Http/Controllers/AnimalController.php:267
* @route '/animals/{animal}/death'
*/
export const death = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: death.url(args, options),
    method: 'post',
})

death.definition = {
    methods: ["post"],
    url: '/animals/{animal}/death',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AnimalController::death
* @see app/Http/Controllers/AnimalController.php:267
* @route '/animals/{animal}/death'
*/
death.url = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return death.definition.url
            .replace('{animal}', parsedArgs.animal.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AnimalController::death
* @see app/Http/Controllers/AnimalController.php:267
* @route '/animals/{animal}/death'
*/
death.post = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: death.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AnimalController::death
* @see app/Http/Controllers/AnimalController.php:267
* @route '/animals/{animal}/death'
*/
const deathForm = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: death.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AnimalController::death
* @see app/Http/Controllers/AnimalController.php:267
* @route '/animals/{animal}/death'
*/
deathForm.post = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: death.url(args, options),
    method: 'post',
})

death.form = deathForm

const AnimalController = { index, show, create, store, transfer, sell, death }

export default AnimalController