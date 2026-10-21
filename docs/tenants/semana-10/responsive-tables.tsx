/* ============================================================
RESPONSIVE TABLES - Módulo Tenants & Auth
DataTable (Desktop) / CardList (Mobile) patterns
============================================================ */
import React, { useState, useMemo } from 'react';
interface Column<T> {
key: string;
header: string;
render?: (row: T, index: number) => React.ReactNode;
width?: string;
align?: 'left' | 'center' | 'right';
sortable?: boolean;
hideMobile?: boolean;
hideTablet?: boolean;
}
interface DataTableProps<T> {
data: T[];
columns: Column<T>[];
keyExtractor: (row: T) => string;
onRowClick?: (row: T) => void;
onRowExpand?: (row: T, expanded: boolean) => void;
expandedRows?: Set<string>;
sortable?: boolean;
sortColumn?: string;
sortDirection?: 'asc' | 'desc';
onSort?: (column: string) => void;
selectionMode?: 'none' | 'single' | 'multiple';
selectedRows?: Set<string>;
onSelectionChange?: (selectedRows: Set<string>) => void;
pagination?: {
page: number;
pageSize: number;
total: number;
onPageChange: (page: number) => void;
onPageSizeChange: (pageSize: number) => void;
};
loading?: boolean;
emptyMessage?: string;
rowActions?: {
label: string;
icon?: React.ReactNode;
onClick: (row: T) => void;
variant?: 'primary' | 'secondary' | 'danger';
condition?: (row: T) => boolean;
}[];
className?: string;
}
interface CardListProps<T> {
data: T[];
renderCard: (row: T, index: number) => React.ReactNode;
keyExtractor: (row: T) => string;
onItemClick?: (row: T) => void;
loading?: boolean;
emptyMessage?: string;
className?: string;
}
export function ResponsiveDataTable<T>({
data,
columns,
keyExtractor,
onRowClick,
onRowExpand,
expandedRows = new Set(),
sortable = false,
sortColumn,
sortDirection = 'asc',
onSort,
selectionMode = 'none',
selectedRows = new Set(),
onSelectionChange,
pagination,
loading = false,
emptyMessage = 'No hay datos disponibles',
rowActions = [],
className = '',
}: DataTableProps<any>) {
const isMobile = false; // Would use useIsMobile() hook
const isTablet = false; // Would use useIsTablet() hook
const visibleColumns = useMemo(() => {
if (isMobile) {
return columns.filter(col => !col.hideMobile);
}
if (isTablet) {
return columns.filter(col => !col.hideTablet);
}
return columns;
}, [columns, isMobile, isTablet]);
const sortedData = useMemo(() => {
if (!sortable || !sortColumn) return data;
return [...data].sort((a, b) => {
const aVal = a[sortColumn];
const bVal = b[sortColumn];
if (aVal < bVal) return sortDirection === 'asc' ? -1 : 1;
if (aVal > bVal) return sortDirection === 'asc' ? 1 : -1;
return 0;
});
}, [data, sortColumn, sortDirection]);
const handleRowClick = (row: any) => {
if (onRowClick) onRowClick(row);
if (onRowExpand) {
const key = keyExtractor(row);
if (expandedRows.has(key)) {
} else {
}
}
};
const handleSelectionChange = (rowKey: string, selected: boolean) => {
if (!onSelectionChange) return;
const newSelection = new Set(selectedRows);
if (selected) {
newSelection.add(rowKey);
} else {
newSelection.delete(rowKey);
}
onSelectionChange(newSelection);
};
const isRowSelected = (rowKey: string) => selectedRows.has(rowKey);
const isRowExpanded = (rowKey: string) => expandedRows.has(rowKey);
if (loading) {
return (
<div className={`data-table-container ${className}`} style={{ position: 'relative' }}>
<div style={{ position: 'absolute', inset: 0, display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'rgba(255,255,255,0.8)', zIndex: 10, }}>
<div className="spinner" style={{ width: '32px', height: '32px', border: '3px solid var(--color-primary-200)', borderTopColor: 'var(--color-primary-600)', borderRadius: '50%', animation: 'spin 0.8s linear infinite', }} />
</div>
<style jsx>{`
@keyframes spin { to { transform: rotate(360deg); } }
`}
</div>
);
}
if (data.length === 0) {
return (
<div className={`data-table-container ${className}`} style={{ padding: '48px 24px', textAlign: 'center' }}>
<div style={{ fontSize: '1.125rem', color: 'var(--color-text-secondary)' }}>
{emptyMessage}
</div>
</div>
);
}
if (isMobile) {
return (
<div className={`card-list-container ${className}`} role="list">
{data.map((row, index) => (
<ResponsiveCard key={keyExtractor(row)} row={row} columns={columns} isExpanded={expandedRows.has(keyExtractor(row))} onToggleExpand={() => onRowExpand?.(row, !expandedRows.has(keyExtractor(row)))} selected={selectedRows.has(keyExtractor(row))} onSelect={(selected) => onSelectionChange?.( new Set(selected ? [...selectedRows, keyExtractor(row)] : Array.from(selectedRows).filter(k => k !== keyExtractor(row))) )} actions={rowActions} onActionClick={(action, row) => action.onClick(row)} />
))}
</div>
);
}
return (
<div className={`data-table-container ${className}`} style={{ overflowX: 'auto' }}>
<table className="data-table" role="grid" aria-label="Tabla de datos">
<thead>
<tr>
{selectionMode !== 'none' && (
<th scope="col" style={{ width: '48px', textAlign: 'center' }}>
<input type="checkbox" checked={selectedRows.size === data.length && data.length > 0} indeterminate={selectedRows.size > 0 && selectedRows.size < data.length} onChange={(e) => { if (e.target.checked) { onSelectionChange?.(new Set(data.map(keyExtractor))); } else { onSelectionChange?.(new Set()); } }} aria-label="Seleccionar todas las filas" />
</th>
)}
{visibleColumns.map((column, index) => (
<th key={column.key} scope="col" style={{ width: column.width, textAlign: column.align || 'left', cursor: column.sortable ? 'pointer' : 'default', userSelect: 'none', whiteSpace: 'nowrap', } as React.CSSProperties} scope="col" aria-sort={sortColumn === column.key ? (sortDirection === 'asc' ? 'ascending' : 'descending') : 'none'} onClick={column.sortable && sortable ? () => onSort?.(column.key) : undefined} aria-sort={sortColumn === column.key ? (sortDirection === 'asc' ? 'ascending' : 'descending') : 'none'} >
<div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
<span>{column.header}</span>
{column.sortable && sortable && (
<span style={{ fontSize: '0.75rem', color: sortColumn === column.key ? 'var(--color-primary-600)' : 'var(--color-text-tertiary)' }}>
{sortDirection === 'asc' ? '▲' : '▼'}
</span>
)}
</div>
</th>
))}
{rowActions.length > 0 && (
<th scope="col" style={{ width: '80px', textAlign: 'center' }}>
Acciones
</th>
)}
</tr>
</thead>
<tbody>
{data.map((row, index) => {
const rowKey = keyExtractor(row);
const expanded = expandedRows.has(rowKey);
const selected = selectedRows.has(rowKey);
return (
<React.Fragment key={rowKey}>
<tr role="row" aria-selected={selected} aria-expanded={expanded} onClick={() => onRowClick?.(row)} style={{ cursor: onRowClick ? 'pointer' : 'default', backgroundColor: selected ? 'var(--color-primary-50)' : index % 2 === 0 ? 'transparent' : 'var(--color-gray-50)', transition: 'background-color 0.15s ease', }} onKeyDown={(e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); onRowClick?.(row); } }} tabIndex={onRowClick ? 0 : -1} >
{selectionMode !== 'none' && (
<td style={{ textAlign: 'center', width: '48px' }}>
<input type="checkbox" checked={selected} onChange={(e) => handleSelectionChange(keyExtractor(row), e.target.checked)} aria-label={`Seleccionar fila ${index + 1}`} />
</td>
)}
{visibleColumns.map((column) => (
<td key={column.key} style={{ textAlign: column.align || 'left', padding: '12px 16px', borderBottom: '1px solid var(--color-border-default)', }} >
{column.render ? column.render(row, index) : row[column.key]}
</td>
))}
{rowActions.length > 0 && (
<td style={{ textAlign: 'center', whiteSpace: 'nowrap' }}>
<div style={{ display: 'flex', gap: '8px', justifyContent: 'center' }}>
{rowActions
.filter(action => !action.condition || action.condition(row))
.map((action, idx) => (
<button key={idx} onClick={(e) => { e.stopPropagation(); action.onClick(row); }} className={`btn btn-${action.variant || 'secondary'} btn-sm`} style={{ padding: '6px 12px', minHeight: '36px' }} aria-label={`${action.label} para fila ${index + 1}`} >
{action.icon && <span aria-hidden="true">{action.icon}</span>}
{action.label}
</button>
))}
</div>
</td>
)}
</tr>
{expanded && (
<tr>
<td colSpan={visibleColumns.length + (selectionMode !== 'none' ? 1 : 0) + (rowActions.length > 0 ? 1 : 0)} style={{ padding: 0 }}>
<div style={{ padding: '16px', background: 'var(--color-gray-50)', borderTop: '1px solid var(--color-border-default)' }}>
<strong>Detalle expandido para: </strong>
{/* Custom expanded content would go here */}
</div>
</td>
</tr>
)}
))}
</tbody>
</table>
)}
</div>
);
}
interface ResponsiveCardProps<T> {
row: T;
columns: Column<any>[];
isExpanded: boolean;
onToggleExpand: () => void;
selected: boolean;
onSelect: (selected: boolean) => void;
actions: any[];
onActionClick: (action: any, row: any) => void;
}
function ResponsiveCard<T>({
row,
columns,
isExpanded,
onToggleExpand,
selected,
onSelect,
actions,
onActionClick,
}: ResponsiveCardProps<any>) {
const rowKey = useMemo(() => JSON.stringify(row), [row]);
return (
<article className="card-item" role="listitem" aria-selected={false} aria-expanded={false} style={{ background: 'var(--color-bg-primary)', border: `1px solid ${selected ? 'var(--color-primary-600)' : 'var(--color-border-default)'}`, borderRadius: '8px', padding: '16px', boxShadow: 'var(--shadow-sm)', transition: 'box-shadow 0.2s, border-color 0.2s', }} style={{ background: 'var(--color-bg-primary)', border: `1px solid ${selected ? 'var(--color-primary-600)' : 'var(--color-border-default)'}`, borderRadius: '8px', padding: '16px', boxShadow: 'var(--shadow-sm)', transition: 'box-shadow 0.2s, border-color 0.2s', } as React.CSSProperties} >
<div className="card-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '12px', }}>
<div style={{ flex: 1, minWidth: 0 }}>
<div style={{ fontWeight: 600, fontSize: '1rem', color: 'var(--color-text-primary)', marginBottom: '4px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', }}>
{/* Main identifier - would use first column or ID */}
{Object.values(row)[0]?.toString() || 'Registro'}
</div>
<div style={{ display: 'flex', flexWrap: 'wrap', gap: '8px', marginTop: '8px', }}>
{Object.entries(row).slice(0, 3).map(([key, value], i) => (
<span key={key} className="card-badge" style={{ display: 'inline-flex', alignItems: 'center', padding: '4px 8px', fontSize: '0.75rem', fontWeight: 500, borderRadius: '9999px', background: 'var(--color-primary-50)', color: 'var(--color-primary-700)', fontSize: '0.75rem', fontWeight: 500, borderRadius: '9999px', padding: '4px 8px', marginRight: '8px', marginBottom: '4px', }} >
{key}: {String(value).slice(0, 30)}
</span>
))}
</div>
</div>
<div className="card-actions" style={{ display: 'flex', gap: '8px', marginTop: '12px', paddingTop: '12px', borderTop: '1px solid var(--color-gray-200)', flexWrap: 'wrap', }}>
<button onClick={(e) => { e.stopPropagation(); }} className="btn btn-secondary btn-sm" style={{ flex: 1 }} >
{isExpanded ? 'Colapsar' : 'Expandir'}
</button>
{/* Row actions would be rendered here */}
</div>
</article>
);
};
export function CardList<T>({
data,
renderCard,
keyExtractor,
onItemClick,
loading = false,
emptyMessage = 'No hay datos disponibles',
className = '',
}: CardListProps<any>) {
const isMobile = true; // Would use useIsMobile()
if (loading) {
return (
<div className={`card-list-container ${className}`} style={{ padding: '24px' }}>
<div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', padding: '48px 24px', }}>
<div className="spinner" style={{ width: '32px', height: '32px', border: '3px solid var(--color-primary-200)', borderTopColor: 'var(--color-primary-600)', borderRadius: '50%', animation: 'spin 0.8s linear infinite', }} />
<style jsx>{`
@keyframes spin { to { transform: rotate(360deg); } }
`}
</div>
</div>
);
}
if (data.length === 0) {
return (
<div className={`card-list-container ${className}`} style={{ padding: '48px 24px', textAlign: 'center' }}>
<div style={{ fontSize: '1.125rem', color: 'var(--color-text-secondary)' }}>
{emptyMessage}
</div>
</div>
);
}
return (
<div className={`card-list-container ${className}`} role="list" style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
{data.map((row, index) => (
<article key={keyExtractor(row)} role="listitem" onClick={() => onItemClick?.(row)} style={{ background: 'var(--color-bg-primary)', border: '1px solid var(--color-border-default)', borderRadius: '8px', padding: '16px', boxShadow: 'var(--shadow-sm)', cursor: onItemClick ? 'pointer' : 'default', transition: 'box-shadow 0.2s, transform 0.1s', }} onMouseEnter={(e) => e.currentTarget.style.boxShadow = 'var(--shadow-md)'} onMouseLeave={(e) => e.currentTarget.style.boxShadow = 'var(--shadow-sm)'} tabIndex={onItemClick ? 0 : -1} onKeyDown={(e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); onItemClick?.(row); } }} >
{renderCard(row, index)}
</article>
))}
</div>
);
}
export function useResponsiveTable<T>(data: T[], columns: Column<T>[]) {
const isMobile = false; // useIsMobile()
const isTablet = false; // useIsTablet()
const visibleColumns = useMemo(() => {
if (isMobile) return columns.filter(c => !c.hideMobile);
if (isTablet) return columns.filter(c => !c.hideTablet);
return columns;
}, [columns]);
const renderCell = useCallback((row: T, column: Column<any>) => {
if (column.render) return column.render(row);
return row[column.key];
}, []);
return { visibleColumns, renderCell };
}
export type { Column, DataTableProps, CardListProps };
export { ResponsiveDataTable, CardList, ResponsiveCard, useResponsiveTable };
