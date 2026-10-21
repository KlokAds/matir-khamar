import { reactive } from 'vue';

/**
 * The app's own confirm and prompt boxes, in place of the browser's grey window.confirm and
 * window.prompt. One host component (AppDialogHost) shows whatever is asked here.
 *
 *   if (await ask.confirm({ title: 'রোল মুছবেন?', danger: true })) { ... }
 *   const reason = await ask.prompt({ title: 'কেন লক খুলছেন?', label: 'কারণ' });
 */
export type DialogRequest = {
    kind: 'confirm' | 'prompt';
    title: string;
    body?: string;
    label?: string;
    placeholder?: string;
    initial?: string;
    inputType?: 'text' | 'password';
    minLength?: number;
    confirmText?: string;
    cancelText?: string;
    danger?: boolean;
};

type Pending = DialogRequest & {
    resolve: (value: string | boolean | null) => void;
};

export const dialogState = reactive<{ current: Pending | null }>({
    current: null,
});

function open(request: DialogRequest): Promise<string | boolean | null> {
    // A dialog already open is answered "no" so its caller is never left waiting.
    dialogState.current?.resolve(request.kind === 'confirm' ? false : null);

    return new Promise((resolve) => {
        dialogState.current = { ...request, resolve };
    });
}

export function answer(value: string | boolean | null): void {
    const current = dialogState.current;
    dialogState.current = null;
    current?.resolve(value);
}

export const ask = {
    confirm: async (request: Omit<DialogRequest, 'kind'>): Promise<boolean> =>
        (await open({ ...request, kind: 'confirm' })) === true,

    prompt: async (
        request: Omit<DialogRequest, 'kind'>,
    ): Promise<string | null> => {
        const value = await open({ ...request, kind: 'prompt' });

        return typeof value === 'string' ? value : null;
    },
};
