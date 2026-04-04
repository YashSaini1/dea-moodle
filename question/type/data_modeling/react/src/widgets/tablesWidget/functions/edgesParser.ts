import { COLORS } from '@colors/colors';
import {
  EDGE_CORNER_RADIUS,
  EDGE_ID_PART,
  EDGE_ID_SOURCE_FIELD_PART,
  EDGE_ID_SOURCE_TABLE_PART,
  EDGE_ID_TARGET_FIELD_PART,
  EDGE_ID_TARGET_TABLE_PART,
  EDGE_MIN_GAP_BETWEEN_TABLES,
  EDGE_PART_WIDTH_BEFORE_END_FIRST_CORNER,
  EDGE_STROKE_WIDTH,
  HANDLE_ID_BOTTOM_SIDE_PART,
  HANDLE_ID_TOP_SIDE_PART,
  HANDLE_LEFT_ID_PART,
  HANDLE_RIGHT_ID_PART,
  MULTI_CONNECTION_LABEL,
  SINGLE_CONNECTION_LABEL,
  SOURCE,
  SVG_MARKER_ARROW_ID,
  SVG_MARKER_LINE_ID,
  TARGET,
  UNDERSCORE,
} from '@constants/constants';
import { IEdgesParserParams } from '@projectTypes/interfaces';
import { EdgeType, GetHandleDirection, GetHandleOrientation } from '@projectTypes/types';
import { emptyEdges } from '@store/initialValues';

const { ATHENS_GRAY } = COLORS;

export const edgesParser = (params: IEdgesParserParams) => {
  const { editorText, parserResult, nodes, edges } = params;

  if (editorText.length > 0) {
    if (parserResult.schemas.length > 0) {
      const allRefsFromAllSchemasArray = parserResult.schemas.map((schema) => {
        const refsFromSchema: EdgeType[] = schema.refs.map((ref) => {
          const endpoints = ref.endpoints.map((endpoint) => {
            const {
              id: endpointId,
              relation: relationLabel,
              fieldNames,
              tableName,
              schemaName,
            } = endpoint;

            const fieldName = fieldNames[0];

            return { endpointId, relationLabel, fieldName, tableName, schemaName };
          });

          const sourceTableSchemaName =
            endpoints[0].schemaName !== null && endpoints[0].schemaName !== 'public'
              ? endpoints[0].schemaName
              : 'public';

          const targetTableSchemaName =
            endpoints[1].schemaName !== null && endpoints[1].schemaName !== 'public'
              ? endpoints[1].schemaName
              : 'public';

          const sourceTableNode = nodes.find((table) => {
            return (
              table.data.tableName === endpoints[0].tableName ||
              table.data.alias === endpoints[0].tableName ||
              table.data.tableName ===
                `${sourceTableSchemaName}.${endpoints[0].tableName}`
            );
          });

          const targetTableNode = nodes.find((table) => {
            return (
              table.data.tableName === endpoints[1].tableName ||
              table.data.alias === endpoints[1].tableName ||
              table.data.tableName ===
                `${targetTableSchemaName}.${endpoints[1].tableName}`
            );
          });

          const sourceFieldNode = sourceTableNode.data.fields.find((field) => {
            return field.leftText === endpoints[0].fieldName;
          });
          const sourceFieldId = sourceFieldNode.id;
          const sourceFieldIndexInTable = sourceTableNode.data.fields.findIndex(
            (field) => {
              return field.leftText === endpoints[0].fieldName;
            },
          );

          const targetFieldNode = targetTableNode.data.fields.find((field) => {
            return field.leftText === endpoints[1].fieldName;
          });
          const targetFieldId = targetFieldNode.id;
          const targetFieldIndexInTable = targetTableNode.data.fields.findIndex(
            (field) => {
              return field.leftText === endpoints[1].fieldName;
            },
          );

          const sourceTableNodeX = sourceTableNode.position.x;
          const targetTableNodeX = targetTableNode.position.x;
          const sourceTableNodeWidth = sourceTableNode.data.tableWidth;
          const targetTableNodeWidth = targetTableNode.data.tableWidth;

          const getHandleDirection: GetHandleDirection = (handleType) => {
            if (handleType === SOURCE) {
              const handleLeftCondition =
                targetTableNodeX + targetTableNodeWidth + EDGE_MIN_GAP_BETWEEN_TABLES <
                sourceTableNodeX;

              if (handleLeftCondition) {
                return HANDLE_LEFT_ID_PART;
              } else return HANDLE_RIGHT_ID_PART;
            } else {
              const handleLeftCondition =
                sourceTableNodeX + sourceTableNodeWidth + EDGE_MIN_GAP_BETWEEN_TABLES <
                targetTableNodeX;

              if (handleLeftCondition) {
                return HANDLE_LEFT_ID_PART;
              } else return HANDLE_RIGHT_ID_PART;
            }
          };

          const getHandleOrientation: GetHandleOrientation = (relationLabel) => {
            if (relationLabel === MULTI_CONNECTION_LABEL) {
              return HANDLE_ID_TOP_SIDE_PART;
            } else return HANDLE_ID_BOTTOM_SIDE_PART;
          };

          const sourceHandleId = `${getHandleDirection(SOURCE)}${getHandleOrientation(
            endpoints[0].relationLabel,
          )}${sourceFieldIndexInTable}${UNDERSCORE}${sourceFieldId}`;

          const targetHandleId = `${getHandleDirection(TARGET)}${getHandleOrientation(
            endpoints[1].relationLabel,
          )}${targetFieldIndexInTable}${UNDERSCORE}${targetFieldId}`;

          const edgeObject: EdgeType = {
            id: `${EDGE_ID_PART}${EDGE_ID_SOURCE_TABLE_PART}${sourceTableNode.id}${EDGE_ID_TARGET_TABLE_PART}${targetTableNode.id}${EDGE_ID_SOURCE_FIELD_PART}${sourceFieldId}${EDGE_ID_TARGET_FIELD_PART}${targetFieldId}`,
            type: 'default',
            source: sourceTableNode.id,
            target: targetTableNode.id,
            sourceHandle: sourceHandleId,
            targetHandle: targetHandleId,
            data: {
              sourceFieldId: sourceFieldId,
              targetFieldId: targetFieldId,
              startEdgeLabel: endpoints[0].relationLabel,
              endEdgeLabel: endpoints[1].relationLabel,
              pathOptions: {
                offset: EDGE_PART_WIDTH_BEFORE_END_FIRST_CORNER, // should be 0 for needed end, because visible edge
                // offset(edge width before edge first corner) should be configured by edge handles algorithm. This
                // algorithm define when edge should change source and target handle IDs, if field have 2 or more
                // handles on both field sides
                borderRadius: EDGE_CORNER_RADIUS,
              },
              markerOptions: {
                startEdgeMarker:
                  endpoints[0].relationLabel === SINGLE_CONNECTION_LABEL
                    ? SVG_MARKER_LINE_ID
                    : SVG_MARKER_ARROW_ID,
                endEdgeMarker:
                  endpoints[1].relationLabel === SINGLE_CONNECTION_LABEL
                    ? SVG_MARKER_LINE_ID
                    : SVG_MARKER_ARROW_ID,
              },
            },
            animated: false,
            style: {
              stroke: ATHENS_GRAY,
              strokeWidth: EDGE_STROKE_WIDTH,
            },
          };

          return edgeObject;
        });

        return refsFromSchema;
      });

      const edgesFromTextEditor = allRefsFromAllSchemasArray.flat(1);
      const edgesFromMouseInteraction = edges.filter((edge) => {
        return !edge.id.includes(EDGE_ID_PART);
      });

      return edgesFromTextEditor.concat(edgesFromMouseInteraction);
    }
  } else {
    return emptyEdges;
  }
};
