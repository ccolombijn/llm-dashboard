import { Sortable } from 'sortablejs';
export function page(): void {
    document.addEventListener('DOMContentLoaded', function () {
        const parts = ((): void => {
            const partsList = document.getElementById('parts') as HTMLElement;
            const orderInput = document.getElementById('parts-order-input') as HTMLInputElement;
            if(!partsList || !orderInput) return;
            const updateOrder = (): void => {
                const parts = Array.from(partsList.children)
                    .map(el => el.getAttribute('data-part-name'));
                orderInput.value = parts.join(',');
            };
            new Sortable(partsList, {
                animation: 150,
                onUpdate: function () {
                    updateOrder();
                }
            });
            updateOrder();
        })();
        ((): void => {
            const controllers = document.getElementById('controllers') as HTMLElement;
            const controllerInput = document.getElementById('controller') as HTMLInputElement;
            if(!controllers || !controllerInput) return;
            const controllersData = JSON.parse(controllers.dataset.controllers);
            const controllersSelect = document.createElement('select') as HTMLSelectElement;
            const methodSelect = document.createElement('select') as HTMLSelectElement;
            const methodInput = document.getElementById('method') as HTMLInputElement;
            const currentController = controllerInput.value ? controllerInput.value : 'PageController';
            const selectMethod = (controller: string = currentController, currentMethod: string = 'show'): HTMLSelectElement => {
                methodSelect.innerHTML = '';
                controllersData[controller].forEach((method: string) => {
                    const option = document.createElement('option') as HTMLOptionElement;
                    option.textContent = method;
                    if (method === currentMethod) option.selected = true;
                    methodSelect.append(option);
                });
                return methodSelect;
            }
            
            controllersSelect.setAttribute('name', 'controller');
            controllersSelect.setAttribute('id', 'controller');
            methodSelect.setAttribute('name', 'method');
            methodSelect.setAttribute('id', 'method');
            controllersSelect.addEventListener('change', (event: Event) =>{
                selectMethod((event.target as HTMLSelectElement).value);
            })
            Object.keys(controllersData).forEach( controller => {
                const option = document.createElement('option') as HTMLOptionElement;
                if(controller === currentController) option.selected = true;
                option.textContent = controller;
                controllersSelect.append(option);
            });

            controllerInput.replaceWith(controllersSelect);
            methodInput.replaceWith(selectMethod(currentController, methodInput.value));

        })();


    });
}