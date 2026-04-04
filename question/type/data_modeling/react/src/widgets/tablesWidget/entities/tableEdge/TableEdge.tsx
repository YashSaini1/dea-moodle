import React, { FC } from 'react';

import { COLORS } from '@colors/colors';
import {
  EDGE_START_END_OFFSET,
  EDGE_STROKE_WIDTH,
  LEFT,
  SINGLE_CONNECTION_LABEL,
} from '@constants/constants';
import { TableEdgeProps } from '@projectTypes/types';
import { GetSmoothStepPathParams } from '@reactflow/core/dist/esm/components/Edges/SmoothStepEdge';
import { getSmoothStepPath, EdgeLabelRenderer } from 'reactflow';

import { EdgeLabel } from './edgeLabel/EdgeLabel';

const { ATHENS_GRAY } = COLORS;

export const TableEdge: FC<TableEdgeProps> = ({
  id,
  sourceX,
  sourceY,
  targetX,
  targetY,
  sourcePosition,
  targetPosition,
  data,
}) => {
  const { borderRadius, offset } = data.pathOptions;
  const { startEdgeMarker, endEdgeMarker } = data.markerOptions;

  const newSourceX =
    sourcePosition === LEFT
      ? sourceX + EDGE_START_END_OFFSET
      : sourceX - EDGE_START_END_OFFSET;
  const newTargetX =
    targetPosition === LEFT
      ? targetX + EDGE_START_END_OFFSET
      : targetX - EDGE_START_END_OFFSET;

  const smoothStepPathParams: GetSmoothStepPathParams = {
    sourceX: newSourceX,
    sourceY,
    sourcePosition,
    targetX: newTargetX,
    targetY,
    targetPosition,
    borderRadius,
    offset,
  };

  const [edgePath] = getSmoothStepPath(smoothStepPathParams);

  const getStartLabelTransform = () => {
    if (data.startEdgeLabel === SINGLE_CONNECTION_LABEL) {
      return `translate(${
        sourcePosition === LEFT ? '-290%' : '185%'
      }, -50%) translate(${sourceX}px,${sourceY}px)`;
    } else {
      return `translate(${
        sourcePosition === LEFT ? '-70%' : '-30%'
      }, -52%) translate(${sourceX}px,${sourceY}px)`;
    }
  };

  const getEndLabelTransform = () => {
    if (data.endEdgeLabel === SINGLE_CONNECTION_LABEL) {
      return `translate(${
        targetPosition === LEFT ? '-290%' : '185%'
      }, -50%) translate(${targetX}px,${targetY}px)`;
    } else {
      return `translate(${
        targetPosition === LEFT ? '-70%' : '-35%'
      }, -52%) translate(${targetX}px,${targetY}px)`;
    }
  };

  return (
    <>
      <path
        d={edgePath}
        fill='none'
        id={id}
        markerEnd={`url(#${endEdgeMarker})`}
        markerStart={`url(#${startEdgeMarker})`}
        stroke={ATHENS_GRAY}
        strokeWidth={EDGE_STROKE_WIDTH}
      />
      <EdgeLabelRenderer>
        {data.startEdgeLabel && (
          <EdgeLabel label={data.startEdgeLabel} transform={getStartLabelTransform()} />
        )}
        {data.endEdgeLabel && (
          <EdgeLabel label={data.endEdgeLabel} transform={getEndLabelTransform()} />
        )}
      </EdgeLabelRenderer>
    </>
  );
};
