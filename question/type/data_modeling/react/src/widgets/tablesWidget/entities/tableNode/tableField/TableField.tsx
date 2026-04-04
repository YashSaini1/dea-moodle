import { MouseEvent, useRef } from 'react';

import {
  BOTTOM,
  CONNECTION_LINE_ID,
  EDGE_CORNER_RADIUS,
  EDGE_ID_PART,
  EDGE_ID_SOURCE_FIELD_PART,
  EDGE_ID_SOURCE_TABLE_PART,
  EDGE_ID_TARGET_FIELD_PART,
  EDGE_ID_TARGET_TABLE_PART,
  EDGE_PART_WIDTH_BEFORE_END_FIRST_CORNER,
  EDGE_POSITION_OFFSET_Y,
  HANDLE_ID_BOTTOM_SIDE_PART,
  HANDLE_ID_TOP_SIDE_PART,
  HANDLE_LEFT_ID_PART,
  HANDLE_RIGHT_ID_PART,
  LEFT,
  MULTI_CONNECTION_LABEL,
  RIGHT,
  SINGLE_CONNECTION_LABEL,
  SVG_MARKER_ARROW_ID,
  SVG_MARKER_LINE_ID,
  TOP,
  UNDERSCORE,
} from '@constants/constants';
import { ITableFieldProps } from '@projectTypes/interfaces';
import { EdgeType, GetHandleValue } from '@projectTypes/types';
import {
  getConnectionSourceTempNode,
  getIsCursorLeave,
} from '@store/redux/stage/stageSelectors';
import {
  setConnectionSourceTempNode,
  setIsCursorLeave,
  setIsNeedToRemoveTempLine,
  setNewEdge,
} from '@store/redux/stage/stageSlice';
import { useAppDispatch } from '@store/redux/store';
import { clsx } from 'clsx';
import { useSelector } from 'react-redux';
import { Position, useEdges } from 'reactflow';

import { FieldHandle } from './fieldHandle/FieldHandle';

export const TableField = (props: ITableFieldProps) => {
  const {
    field: { leftText, rightText, id: fieldId, isPrimaryKey },
    sizes: { maxLeft, maxRight, initCoordsForHandle },
    tableId,
    fieldIndex,
  } = props;

  const dispatch = useAppDispatch();

  const fieldRef = useRef<HTMLLIElement | null>(null);

  const connectionSourceTempNode = useSelector(getConnectionSourceTempNode);
  const isCursorLeave = useSelector(getIsCursorLeave);

  const edges: EdgeType[] = useEdges();

  const temporaryEdgeId = `${EDGE_ID_PART}${EDGE_ID_SOURCE_TABLE_PART}${
    connectionSourceTempNode ? connectionSourceTempNode.tableId : 'null'
  }${EDGE_ID_TARGET_TABLE_PART}${tableId}${EDGE_ID_SOURCE_FIELD_PART}${
    connectionSourceTempNode ? connectionSourceTempNode.fieldId : 'null'
  }${EDGE_ID_TARGET_FIELD_PART}${fieldId}`;

  const targetFieldIsNotSameFieldCondition = connectionSourceTempNode
    ? connectionSourceTempNode.fieldId !== fieldId
    : false;
  const isCurrentEdgeExist = !!edges.find((edge) => {
    return edge.id === temporaryEdgeId;
  });
  const sourceFieldHasNoEdgeToTargetFieldCondition = !isCurrentEdgeExist;
  const isPossibleToCreateTemporaryEdge =
    targetFieldIsNotSameFieldCondition && sourceFieldHasNoEdgeToTargetFieldCondition;

  const forbiddenCursorCondition =
    !targetFieldIsNotSameFieldCondition || !sourceFieldHasNoEdgeToTargetFieldCondition;

  const isForbiddenCursor =
    connectionSourceTempNode && isCursorLeave && forbiddenCursorCondition;

  const topLeftHandleId = `${HANDLE_LEFT_ID_PART}${HANDLE_ID_TOP_SIDE_PART}${fieldIndex}${UNDERSCORE}${fieldId}`;
  const bottomLeftHandleId = `${HANDLE_LEFT_ID_PART}${HANDLE_ID_BOTTOM_SIDE_PART}${fieldIndex}${UNDERSCORE}${fieldId}`;
  const topRightHandleId = `${HANDLE_RIGHT_ID_PART}${HANDLE_ID_TOP_SIDE_PART}${fieldIndex}${UNDERSCORE}${fieldId}`;
  const bottomRightHandleId = `${HANDLE_RIGHT_ID_PART}${HANDLE_ID_BOTTOM_SIDE_PART}${fieldIndex}${UNDERSCORE}${fieldId}`;

  const getHandleId: GetHandleValue = (params) => {
    const { handlePosition, clientX, left, right } = params;

    if (handlePosition === BOTTOM) {
      return Math.abs(clientX - left) < Math.abs(clientX - right)
        ? `${HANDLE_LEFT_ID_PART}${HANDLE_ID_BOTTOM_SIDE_PART}${fieldIndex}${UNDERSCORE}${fieldId}`
        : `${HANDLE_RIGHT_ID_PART}${HANDLE_ID_BOTTOM_SIDE_PART}${fieldIndex}${UNDERSCORE}${fieldId}`;
    } else {
      return Math.abs(clientX - left) < Math.abs(clientX - right)
        ? `${HANDLE_LEFT_ID_PART}${HANDLE_ID_TOP_SIDE_PART}${fieldIndex}${UNDERSCORE}${fieldId}`
        : `${HANDLE_RIGHT_ID_PART}${HANDLE_ID_TOP_SIDE_PART}${fieldIndex}${UNDERSCORE}${fieldId}`;
    }
  };

  // when the user clicks on field, the functionality of connecting 2 fields is launched to determine which hidden
  // handle element (sourceHandle) will be used for the edge, we determine which side of the field was clicked (left
  // or right)
  const onMouseDown = ({ clientX }: MouseEvent) => {
    if (fieldRef.current) {
      const { left, right } = fieldRef.current.getBoundingClientRect();

      dispatch(
        setConnectionSourceTempNode({
          fieldId: fieldId,
          tableId: tableId,
          sourceHandleId: getHandleId({ handlePosition: BOTTOM, clientX, left, right }),
        }),
      );
    }
  };

  const onMouseEnter = (event: MouseEvent) => {
    if (connectionSourceTempNode) {
      const { clientX } = event;

      if (isPossibleToCreateTemporaryEdge) {
        const { left, right } = fieldRef.current.getBoundingClientRect();

        const temporaryEdge: EdgeType = {
          id: CONNECTION_LINE_ID, // temporaryEdgeId
          data: {
            sourceFieldId: fieldId,
            targetFieldId: connectionSourceTempNode.fieldId,
            startEdgeLabel: SINGLE_CONNECTION_LABEL,
            endEdgeLabel: MULTI_CONNECTION_LABEL,
            pathOptions: {
              offset: EDGE_PART_WIDTH_BEFORE_END_FIRST_CORNER,
              borderRadius: EDGE_CORNER_RADIUS,
            },
            markerOptions: {
              startEdgeMarker: SVG_MARKER_LINE_ID,
              endEdgeMarker: SVG_MARKER_ARROW_ID,
            },
          },
          source: connectionSourceTempNode.tableId,
          sourceHandle: connectionSourceTempNode.sourceHandleId,
          target: tableId,
          targetHandle: getHandleId({ handlePosition: TOP, clientX, left, right }),
          animated: true,
          type: 'default',
        };

        dispatch(setNewEdge(temporaryEdge));
      } else {
        dispatch(setIsNeedToRemoveTempLine(true));
      }
    }
  };

  const onMouseLeave = () => {
    if (connectionSourceTempNode && !isCursorLeave) {
      dispatch(setIsCursorLeave(true));
    }
  };

  const sourceEdges = edges.filter((edge) => {
    return edge.data.sourceFieldId === fieldId;
  });

  const targetEdges = edges.filter((edge) => {
    return edge.data.targetFieldId === fieldId;
  });

  const leftFieldSideHandlesSplitCondition = (): boolean => {
    const sourceEdgesLeft = sourceEdges.filter((edge) => {
      return edge.sourceHandle.includes(LEFT);
    });

    const targetEdgesLeft = targetEdges.filter((edge) => {
      return edge.targetHandle.includes(LEFT);
    });

    const hasSingleConnectionInSourceEdgesLeftCondition = sourceEdgesLeft.some((edge) => {
      return edge.data.startEdgeLabel === SINGLE_CONNECTION_LABEL;
    });

    const hasMultiConnectionInSourceEdgesLeftCondition = sourceEdgesLeft.some((edge) => {
      return edge.data.startEdgeLabel === MULTI_CONNECTION_LABEL;
    });

    const hasSingleConnectionInTargetEdgesLeftCondition = targetEdgesLeft.some((edge) => {
      return edge.data.endEdgeLabel === SINGLE_CONNECTION_LABEL;
    });

    const hasMultiConnectionInTargetEdgesLeftCondition = targetEdgesLeft.some((edge) => {
      return edge.data.endEdgeLabel === MULTI_CONNECTION_LABEL;
    });

    return (
      (hasSingleConnectionInSourceEdgesLeftCondition ||
        hasSingleConnectionInTargetEdgesLeftCondition) &&
      (hasMultiConnectionInSourceEdgesLeftCondition ||
        hasMultiConnectionInTargetEdgesLeftCondition)
    );
  };

  const rightFieldSideHandlesSplitCondition = (): boolean => {
    const sourceEdgesRight = sourceEdges.filter((edge) => {
      return edge.sourceHandle.includes(RIGHT);
    });

    const targetEdgesRight = targetEdges.filter((edge) => {
      return edge.targetHandle.includes(RIGHT);
    });

    const hasSingleConnectionInSourceEdgesRightCondition = sourceEdgesRight.some(
      (edge) => {
        return edge.data.startEdgeLabel === SINGLE_CONNECTION_LABEL;
      },
    );

    const hasMultiConnectionInSourceEdgesRightCondition = sourceEdgesRight.some(
      (edge) => {
        return edge.data.startEdgeLabel === MULTI_CONNECTION_LABEL;
      },
    );

    const hasSingleConnectionInTargetEdgesRightCondition = targetEdgesRight.some(
      (edge) => {
        return edge.data.endEdgeLabel === SINGLE_CONNECTION_LABEL;
      },
    );

    const hasMultiConnectionInTargetEdgesRightCondition = targetEdgesRight.some(
      (edge) => {
        return edge.data.endEdgeLabel === MULTI_CONNECTION_LABEL;
      },
    );

    return (
      (hasSingleConnectionInSourceEdgesRightCondition ||
        hasSingleConnectionInTargetEdgesRightCondition) &&
      (hasMultiConnectionInSourceEdgesRightCondition ||
        hasMultiConnectionInTargetEdgesRightCondition)
    );
  };

  const handlesLeftOffsetY = leftFieldSideHandlesSplitCondition()
    ? EDGE_POSITION_OFFSET_Y
    : 0;

  const handlesRightOffsetY = rightFieldSideHandlesSplitCondition()
    ? EDGE_POSITION_OFFSET_Y
    : 0;

  return (
    <li
      className={clsx(`FieldContainer ${isForbiddenCursor ? 'SelectForbidden' : ''}`)}
      /*onMouseDown={onMouseDown}
      onMouseEnter={onMouseEnter}
      onMouseLeave={onMouseLeave}*/
      ref={fieldRef}
    >
      <div
        className={clsx('Field', 'FieldTextLeft', {
          ['FieldTextPrimaryKey']: isPrimaryKey,
        })}
        style={maxLeft ? { width: `${maxLeft}px` } : {}}
      >
        <span>{leftText}</span>
      </div>
      <div
        className={clsx('Field', 'FieldTextRight', {
          ['FieldTextPrimaryKey']: isPrimaryKey,
        })}
        style={maxRight ? { width: `${maxRight}px` } : {}}
      >
        <span>{rightText}</span>
      </div>
      <FieldHandle
        id={topLeftHandleId}
        position={Position.Left}
        style={{
          top: initCoordsForHandle[fieldIndex] - handlesLeftOffsetY,
          visibility: 'hidden',
        }}
        tableId={tableId}
      />
      <FieldHandle
        id={bottomLeftHandleId}
        position={Position.Left}
        style={{
          top: initCoordsForHandle[fieldIndex] + handlesLeftOffsetY,
          visibility: 'hidden',
        }}
        tableId={tableId}
      />
      <FieldHandle
        id={topRightHandleId}
        position={Position.Right}
        style={{
          top: initCoordsForHandle[fieldIndex] - handlesRightOffsetY,
          visibility: 'hidden',
        }}
        tableId={tableId}
      />
      <FieldHandle
        id={bottomRightHandleId}
        position={Position.Right}
        style={{
          top: initCoordsForHandle[fieldIndex] + handlesRightOffsetY,
          visibility: 'hidden',
        }}
        tableId={tableId}
      />
    </li>
  );
};
