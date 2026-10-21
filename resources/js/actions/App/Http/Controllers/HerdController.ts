import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\HerdController::storeBreeding
* @see app/Http/Controllers/HerdController.php:56
* @route '/animals/{animal}/breedings'
*/
export const storeBreeding = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeBreeding.url(args, options),
    method: 'post',
})

storeBreeding.definition = {
    methods: ["post"],
    url: '/animals/{animal}/breedings',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\HerdController::storeBreeding
* @see app/Http/Controllers/HerdController.php:56
* @route '/animals/{animal}/breedings'
*/
storeBreeding.url = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return storeBreeding.definition.url
            .replace('{animal}', parsedArgs.animal.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\HerdController::storeBreeding
* @see app/Http/Controllers/HerdController.php:56
* @route '/animals/{animal}/breedings'
*/
storeBreeding.post = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeBreeding.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\HerdController::storeBreeding
* @see app/Http/Controllers/HerdController.php:56
* @route '/animals/{animal}/breedings'
*/
const storeBreedingForm = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeBreeding.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\HerdController::storeBreeding
* @see app/Http/Controllers/HerdController.php:56
* @route '/animals/{animal}/breedings'
*/
storeBreedingForm.post = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeBreeding.url(args, options),
    method: 'post',
})

storeBreeding.form = storeBreedingForm

/**
* @see \App\Http\Controllers\HerdController::updateBreeding
* @see app/Http/Controllers/HerdController.php:78
* @route '/breedings/{breeding}'
*/
export const updateBreeding = (args: { breeding: number | { id: number } } | [breeding: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateBreeding.url(args, options),
    method: 'put',
})

updateBreeding.definition = {
    methods: ["put"],
    url: '/breedings/{breeding}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\HerdController::updateBreeding
* @see app/Http/Controllers/HerdController.php:78
* @route '/breedings/{breeding}'
*/
updateBreeding.url = (args: { breeding: number | { id: number } } | [breeding: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { breeding: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { breeding: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            breeding: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        breeding: typeof args.breeding === 'object'
        ? args.breeding.id
        : args.breeding,
    }

    return updateBreeding.definition.url
            .replace('{breeding}', parsedArgs.breeding.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\HerdController::updateBreeding
* @see app/Http/Controllers/HerdController.php:78
* @route '/breedings/{breeding}'
*/
updateBreeding.put = (args: { breeding: number | { id: number } } | [breeding: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateBreeding.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\HerdController::updateBreeding
* @see app/Http/Controllers/HerdController.php:78
* @route '/breedings/{breeding}'
*/
const updateBreedingForm = (args: { breeding: number | { id: number } } | [breeding: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateBreeding.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\HerdController::updateBreeding
* @see app/Http/Controllers/HerdController.php:78
* @route '/breedings/{breeding}'
*/
updateBreedingForm.put = (args: { breeding: number | { id: number } } | [breeding: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateBreeding.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updateBreeding.form = updateBreedingForm

/**
* @see \App\Http\Controllers\HerdController::storeWeight
* @see app/Http/Controllers/HerdController.php:133
* @route '/animals/{animal}/weights'
*/
export const storeWeight = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeWeight.url(args, options),
    method: 'post',
})

storeWeight.definition = {
    methods: ["post"],
    url: '/animals/{animal}/weights',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\HerdController::storeWeight
* @see app/Http/Controllers/HerdController.php:133
* @route '/animals/{animal}/weights'
*/
storeWeight.url = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return storeWeight.definition.url
            .replace('{animal}', parsedArgs.animal.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\HerdController::storeWeight
* @see app/Http/Controllers/HerdController.php:133
* @route '/animals/{animal}/weights'
*/
storeWeight.post = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeWeight.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\HerdController::storeWeight
* @see app/Http/Controllers/HerdController.php:133
* @route '/animals/{animal}/weights'
*/
const storeWeightForm = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeWeight.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\HerdController::storeWeight
* @see app/Http/Controllers/HerdController.php:133
* @route '/animals/{animal}/weights'
*/
storeWeightForm.post = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeWeight.url(args, options),
    method: 'post',
})

storeWeight.form = storeWeightForm

/**
* @see \App\Http\Controllers\HerdController::breeding
* @see app/Http/Controllers/HerdController.php:25
* @route '/breeding'
*/
export const breeding = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: breeding.url(options),
    method: 'get',
})

breeding.definition = {
    methods: ["get","head"],
    url: '/breeding',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\HerdController::breeding
* @see app/Http/Controllers/HerdController.php:25
* @route '/breeding'
*/
breeding.url = (options?: RouteQueryOptions) => {
    return breeding.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\HerdController::breeding
* @see app/Http/Controllers/HerdController.php:25
* @route '/breeding'
*/
breeding.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: breeding.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\HerdController::breeding
* @see app/Http/Controllers/HerdController.php:25
* @route '/breeding'
*/
breeding.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: breeding.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\HerdController::breeding
* @see app/Http/Controllers/HerdController.php:25
* @route '/breeding'
*/
const breedingForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: breeding.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\HerdController::breeding
* @see app/Http/Controllers/HerdController.php:25
* @route '/breeding'
*/
breedingForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: breeding.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\HerdController::breeding
* @see app/Http/Controllers/HerdController.php:25
* @route '/breeding'
*/
breedingForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: breeding.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

breeding.form = breedingForm

/**
* @see \App\Http\Controllers\HerdController::batches
* @see app/Http/Controllers/HerdController.php:99
* @route '/batches'
*/
export const batches = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: batches.url(options),
    method: 'get',
})

batches.definition = {
    methods: ["get","head"],
    url: '/batches',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\HerdController::batches
* @see app/Http/Controllers/HerdController.php:99
* @route '/batches'
*/
batches.url = (options?: RouteQueryOptions) => {
    return batches.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\HerdController::batches
* @see app/Http/Controllers/HerdController.php:99
* @route '/batches'
*/
batches.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: batches.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\HerdController::batches
* @see app/Http/Controllers/HerdController.php:99
* @route '/batches'
*/
batches.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: batches.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\HerdController::batches
* @see app/Http/Controllers/HerdController.php:99
* @route '/batches'
*/
const batchesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: batches.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\HerdController::batches
* @see app/Http/Controllers/HerdController.php:99
* @route '/batches'
*/
batchesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: batches.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\HerdController::batches
* @see app/Http/Controllers/HerdController.php:99
* @route '/batches'
*/
batchesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: batches.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

batches.form = batchesForm

/**
* @see \App\Http\Controllers\HerdController::storeBatch
* @see app/Http/Controllers/HerdController.php:111
* @route '/batches'
*/
export const storeBatch = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeBatch.url(options),
    method: 'post',
})

storeBatch.definition = {
    methods: ["post"],
    url: '/batches',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\HerdController::storeBatch
* @see app/Http/Controllers/HerdController.php:111
* @route '/batches'
*/
storeBatch.url = (options?: RouteQueryOptions) => {
    return storeBatch.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\HerdController::storeBatch
* @see app/Http/Controllers/HerdController.php:111
* @route '/batches'
*/
storeBatch.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeBatch.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\HerdController::storeBatch
* @see app/Http/Controllers/HerdController.php:111
* @route '/batches'
*/
const storeBatchForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeBatch.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\HerdController::storeBatch
* @see app/Http/Controllers/HerdController.php:111
* @route '/batches'
*/
storeBatchForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeBatch.url(options),
    method: 'post',
})

storeBatch.form = storeBatchForm

/**
* @see \App\Http\Controllers\HerdController::updateBatch
* @see app/Http/Controllers/HerdController.php:122
* @route '/batches/{batch}'
*/
export const updateBatch = (args: { batch: number | { id: number } } | [batch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateBatch.url(args, options),
    method: 'put',
})

updateBatch.definition = {
    methods: ["put"],
    url: '/batches/{batch}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\HerdController::updateBatch
* @see app/Http/Controllers/HerdController.php:122
* @route '/batches/{batch}'
*/
updateBatch.url = (args: { batch: number | { id: number } } | [batch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { batch: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { batch: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            batch: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        batch: typeof args.batch === 'object'
        ? args.batch.id
        : args.batch,
    }

    return updateBatch.definition.url
            .replace('{batch}', parsedArgs.batch.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\HerdController::updateBatch
* @see app/Http/Controllers/HerdController.php:122
* @route '/batches/{batch}'
*/
updateBatch.put = (args: { batch: number | { id: number } } | [batch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateBatch.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\HerdController::updateBatch
* @see app/Http/Controllers/HerdController.php:122
* @route '/batches/{batch}'
*/
const updateBatchForm = (args: { batch: number | { id: number } } | [batch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateBatch.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\HerdController::updateBatch
* @see app/Http/Controllers/HerdController.php:122
* @route '/batches/{batch}'
*/
updateBatchForm.put = (args: { batch: number | { id: number } } | [batch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateBatch.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updateBatch.form = updateBatchForm

const HerdController = { storeBreeding, updateBreeding, storeWeight, breeding, batches, storeBatch, updateBatch }

export default HerdController