import { TABLE_ELEMENT_ID_PART } from '@constants/constants';
import { ITablesParserParams } from '@projectTypes/interfaces';
import { FieldType, NodeType } from '@projectTypes/types';
import { emptyNodes } from '@store/initialValues';

import { noTouchedTablesRanking } from './noTouchedTablesRanking';
import { tablesRanking } from './tablesRanking';

export const tablesParser = (params: ITablesParserParams) => {
  const { editorText, parserResult, nodes } = params;

  if (editorText.length > 0) {
    if (parserResult.schemas.length > 0) {
      const allTablesFromAllSchemasArray = parserResult.schemas.map((schema) => {
        const tablesFromSchema: NodeType[] = schema.tables.map((table) => {
          const {
            id: tableId,
            name: tableName,
            alias: tableAlias,
            fields: tableFields,
            indexes,
          } = table;

          // different columns in indexes may contain identical field names
          const fieldsNamesWithPrimaryKeyFromIndexes: Array<string[]> = indexes.map(
            (index) => {
              if (index.pk) {
                return index.columns.map((column) => {
                  return column.value;
                });
              }
            },
          );

          const flattedFieldsNamesWithPrimaryKeyFromIndexes =
            fieldsNamesWithPrimaryKeyFromIndexes.flat(1);

          // different columns in indexes may contain identical field names
          const filteredFieldsNamesWithPrimaryKeyFromIndexes =
            flattedFieldsNamesWithPrimaryKeyFromIndexes.filter((fieldName, index) => {
              return (
                flattedFieldsNamesWithPrimaryKeyFromIndexes.indexOf(fieldName) === index
              );
            });

          const currentTableInNodes = nodes.find((node) => {
            return node.id === String(tableId);
          });

          const tableElement = document.getElementById(
            `${TABLE_ELEMENT_ID_PART}${tableId}`,
          ) as HTMLDivElement;

          const tableObject: NodeType = {
            id: String(tableId),
            type: 'sqlTable',
            position: currentTableInNodes ? currentTableInNodes.position : { x: 0, y: 0 },
            data: {
              isTouched: currentTableInNodes ? currentTableInNodes.data.isTouched : false,
              tableWidth: tableElement ? tableElement.offsetWidth : 0,
              tableHeight: tableElement ? tableElement.offsetHeight : 0,
              alias: tableAlias ? tableAlias : null,
              tableName:
                parserResult.schemas.length > 1
                  ? `${schema.name}.${tableName}`
                  : tableName,
              fields: tableFields.map((field) => {
                const { id, name: leftText, pk: primaryKeyFromField } = field;
                const rightText = field.type.type_name;

                const primaryKeyFromIndexes =
                  filteredFieldsNamesWithPrimaryKeyFromIndexes.some(
                    (fieldName) => fieldName === leftText,
                  );

                const isPrimaryKey = primaryKeyFromField ?? primaryKeyFromIndexes;

                const fieldObject: FieldType = {
                  id,
                  isPrimaryKey,
                  leftText,
                  rightText,
                };

                return fieldObject;
              }),
            },
          };

          return tableObject;
        });

        return tablesFromSchema;
      });

      const tables = allTablesFromAllSchemasArray.flat(1);

      const hasTouchedTablesCondition = tables.some(
        (table) => table.data.isTouched === true,
      );

      if (hasTouchedTablesCondition) {
        return tablesRanking({ tables });
      } else {
        return noTouchedTablesRanking({ tables });
      }
    }
  } else {
    return emptyNodes;
  }
};
