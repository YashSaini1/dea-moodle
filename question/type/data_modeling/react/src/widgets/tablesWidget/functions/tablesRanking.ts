import { TABLES_GAP, TABLES_GRID_COLUMN_MAX_QUANTITY } from '@constants/constants';
import { ITablesColumn } from '@projectTypes/interfaces';
import { TablesRanking } from '@projectTypes/types';
import { cloneDeep } from 'lodash';

export const tablesRanking: TablesRanking = (params) => {
  const { tables } = params;

  const tempColumns: ITablesColumn[] = [];

  // 1. Create a dynamic number of column arrays
  for (let i = 0; i < TABLES_GRID_COLUMN_MAX_QUANTITY; i += 1) {
    tempColumns.push({ tables: [], width: 0, index: i });
  }

  // 2. Iterating over the tables. Push the table with the desired Y value to the desired column
  tables.forEach((table, tableIndex) => {
    const currentColumnIndex = tableIndex % tempColumns.length;

    const tableClone = cloneDeep(table);

    const emptyTablesArrayInCurrentColumnCondition =
      tempColumns[currentColumnIndex].tables.length === 0;

    if (emptyTablesArrayInCurrentColumnCondition) {
      tableClone.position = { x: 0, y: 0 };
    } else {
      tableClone.position.y =
        tempColumns[currentColumnIndex].tables[
          tempColumns[currentColumnIndex].tables.length - 1
        ].position.y +
        tempColumns[currentColumnIndex].tables[
          tempColumns[currentColumnIndex].tables.length - 1
        ].data.tableHeight +
        TABLES_GAP;
    }

    tempColumns[currentColumnIndex].tables.push(tableClone);
  });

  // 3. Iterating over the columns. In the column iterate over the tables. We find the longest table, take its length,
  // and set this value for the current column
  tempColumns.forEach((tempColumn) => {
    tempColumn.tables.forEach((table) => {
      const tableClone = cloneDeep(table);
      const tableWidth = tableClone.data.tableWidth;

      // the problem is in rerendering - when a value is set with new tables that did not exist before, they are simply
      // drawn, but their width is 0, because we need to take the width of the table only after its first render,
      // because the table width is set by the hook
      if (tempColumn.width < tableWidth) {
        tempColumn.width = tableWidth;
      }
    });
  });

  // 4. Iterating over the columns. In the column iterate over the tables. First column is ignored, because for the
  // tables in this column do not need to be set X value, since the tables are already on the left.
  // If column isn't the first, need to set for all tables in their X value the summed widths of all
  // previous columns + indent * index of the current column
  tempColumns.forEach((column, columnIndex) => {
    const firstColumnCondition = columnIndex === 0;

    if (firstColumnCondition) {
      return;
    } else if (column.width !== 0) {
      let currentColumnWidthsSum = 0;

      tempColumns.forEach((tempColumn) => {
        if (tempColumn.index < column.index) {
          currentColumnWidthsSum = currentColumnWidthsSum + tempColumn.width;
        }
      });

      const newPositionX = currentColumnWidthsSum + TABLES_GAP * column.index;

      column.tables.forEach((table) => {
        table.position.x = newPositionX;
      });
    }
  });

  const columnsWithNewPositions = tempColumns.map((column) => {
    return column.tables;
  });

  const unfilteredNoTouchedTables = columnsWithNewPositions.flat(1);
  const noTouchedTables = unfilteredNoTouchedTables.filter((table) => {
    return table.data.isTouched === false;
  });

  const touchedTables = tables.filter((table) => {
    return table.data.isTouched === true;
  });

  return [...touchedTables, ...noTouchedTables];
};
