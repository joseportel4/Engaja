import { AllCommunityModule, ModuleRegistry, createGrid } from "ag-grid-community";

ModuleRegistry.registerModules([AllCommunityModule]);

const htmlCellRenderer = (params) => params.value ?? "";

// Cabeçalho com HTML cru (ex.: o link de ordenação server-side já usado nos
// relatórios). Usado quando a coluna não deve ter sort nativo do grid — quem
// decide a ordenação é o link, via reload de página com querystring.
class HtmlHeaderComponent {
    init(params) {
        this.eGui = document.createElement("div");
        this.eGui.className = "dt-header-html";
        this.eGui.innerHTML = params.headerHtml ?? "";
    }

    getGui() {
        return this.eGui;
    }

    refresh() {
        return false;
    }
}

const buildColumnDefs = (columns, rowClassField) =>
    columns.map((col) => {
        if (col.children) {
            return {
                headerName: col.headerName,
                groupId: col.groupId ?? col.headerName,
                marryChildren: true,
                children: buildColumnDefs(col.children, rowClassField),
            };
        }

        const def = {
            field: col.field,
            headerName: col.headerName,
            colId: col.field,
            sortable: col.html ? false : col.sortable ?? true,
            filter: false,
            resizable: col.resizable ?? true,
            hide: col.hide ?? false,
            flex: col.flex ?? 1,
            minWidth: col.minWidth,
            width: col.width,
            pinned: col.pinned,
            cellClass: col.cellClass,
            headerClass: col.headerClass,
        };

        if (col.headerHtml) {
            def.headerComponent = HtmlHeaderComponent;
            def.headerComponentParams = { headerHtml: col.headerHtml };
        }

        // Mescla esta célula com as N colunas seguintes quando a linha tem o
        // rowClass indicado (ex.: linhas de "Subtotal"/"TOTAL" que mostram um
        // rótulo único ocupando o espaço de várias colunas, como no colspan
        // das tabelas Blade originais).
        if (col.colSpanWhen && col.colSpanCount) {
            const matchValues = Array.isArray(col.colSpanWhen) ? col.colSpanWhen : [col.colSpanWhen];
            def.colSpan = (params) =>
                rowClassField && matchValues.includes(params.data?.[rowClassField]) ? col.colSpanCount : 1;
        }

        if (col.html) {
            def.cellRenderer = htmlCellRenderer;
            def.cellStyle = {
                display: "flex",
                alignItems: "center",
                height: "100%",
                overflow: "visible",
            };
        } else {
            // Trunca com "..." (comportamento padrão do AG Grid para células de
            // texto) e mostra o conteúdo completo em tooltip só quando truncado.
            def.tooltipValueGetter = (params) =>
                params.value !== undefined && params.value !== null && params.value !== ""
                    ? String(params.value)
                    : undefined;
        }

        return def;
    });

const initTable = (el) => {
    const columns = JSON.parse(el.dataset.columns || "[]");
    const rows = JSON.parse(el.dataset.rows || "[]");
    const pagination = el.dataset.pagination !== "false";
    const pageSize = Number(el.dataset.pageSize || 15);
    const rowSelectionMode = el.dataset.rowSelection || null;
    const domLayout = el.dataset.domLayout || "autoHeight";
    const rowClassField = el.dataset.rowClassField || null;

    const hasHtmlColumn = columns.some((col) => col.html);

    const gridOptions = {
        columnDefs: buildColumnDefs(columns, rowClassField),
        rowData: rows,
        pagination,
        paginationPageSize: pageSize,
        paginationPageSizeSelector: false,
        domLayout,
        rowHeight: hasHtmlColumn ? 52 : undefined,
        suppressCellFocus: true,
        tooltipShowMode: "whenTruncated",
        tooltipShowDelay: 300,
        localeText: {
            page: "Página",
            to: "até",
            of: "de",
            noRowsToShow: "Nenhum registro encontrado.",
            first: "Primeira",
            previous: "Anterior",
            next: "Próxima",
            last: "Última",
        },
    };

    if (rowSelectionMode) {
        gridOptions.rowSelection = {
            mode: rowSelectionMode === "multiple" ? "multiRow" : "singleRow",
            checkboxes: true,
            headerCheckbox: rowSelectionMode === "multiple",
        };
        gridOptions.onSelectionChanged = (event) => {
            const selectedRows = event.api.getSelectedRows();
            el.dispatchEvent(
                new CustomEvent("datatable:selection-changed", { detail: { rows: selectedRows } }),
            );
        };
    }

    if (rowClassField) {
        gridOptions.getRowClass = (params) => params.data?.[rowClassField] || undefined;
    }

    el.dataset.agGridInitialized = "true";
    el._agGridApi = createGrid(el, gridOptions);
};

const initTablesIfNeeded = () => {
    document.querySelectorAll("[data-ag-grid]").forEach((el) => {
        if (el.dataset.agGridInitialized) {
            return;
        }

        initTable(el);
    });
};

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initTablesIfNeeded, { once: true });
} else {
    initTablesIfNeeded();
}

// AG Grid posiciona linhas com `transform: translateY(...)` para virtualização,
// o que cria um containing block próprio e quebra `position: fixed` em dropdowns
// dentro de células — o menu fica preso ao viewport scrollável do grid em vez do
// viewport da página. Solução: "portar" o menu para o <body> enquanto estiver
// aberto, para que o Popper o posicione relativo à página, e devolvê-lo ao lugar
// original ao fechar.
const portaledMenus = new WeakMap();

document.addEventListener("show.bs.dropdown", (event) => {
    const toggle = event.target;

    if (!(toggle instanceof HTMLElement) || !toggle.closest(".ag-cell")) {
        return;
    }

    const wrapper = toggle.closest(".dropdown");
    const menu = wrapper?.querySelector(".dropdown-menu");

    if (!wrapper || !menu) {
        return;
    }

    portaledMenus.set(wrapper, menu);
    document.body.appendChild(menu);
});

document.addEventListener("hidden.bs.dropdown", (event) => {
    const toggle = event.target;

    if (!(toggle instanceof HTMLElement)) {
        return;
    }

    const wrapper = toggle.closest(".dropdown");
    const menu = wrapper && portaledMenus.get(wrapper);

    if (wrapper && menu) {
        wrapper.appendChild(menu);
        portaledMenus.delete(wrapper);
    }
});
