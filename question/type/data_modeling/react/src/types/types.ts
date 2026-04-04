import { MouseEvent } from 'react';

import {
  HANDLE_ID_BOTTOM_SIDE_PART,
  HANDLE_ID_TOP_SIDE_PART,
  HANDLE_LEFT_ID_PART,
  HANDLE_RIGHT_ID_PART,
  MULTI_CONNECTION_LABEL,
  SINGLE_CONNECTION_LABEL,
} from '@constants/constants';
import { SmoothStepEdgeProps } from '@reactflow/core/dist/esm/types/edges';
import { Edge, Node } from 'reactflow';

import { ITableEdgeData, ITableNodeData, ITablesRankingParams } from './interfaces';

export type NodeType = Node<ITableNodeData, string>;
export type EdgeType = Edge<ITableEdgeData>;

type NodeDataFieldForServerType = Omit<ITableNodeData, 'tableWidth' | 'tableHeight'>;
export type NodeForServerType = Omit<NodeType, 'data' | 'type'> & {
  data: NodeDataFieldForServerType;
};

type EdgeDataFieldForServerType = Omit<ITableEdgeData, 'pathOptions'>;
export type EdgeForServerType = Omit<
  EdgeType,
  'animated' | 'data' | 'style' | 'sourceHandle' | 'targetHandle' | 'type'
> & {
  data: EdgeDataFieldForServerType;
};

export type FieldType = {
  id: number;
  leftText: string;
  rightText: string;
  isPrimaryKey: boolean;
};

export type Widths = {
  left: number[];
  right: number[];
  coords: number[];
};

export type EdgeMarkersOptions = {
  startEdgeMarker: string;
  endEdgeMarker: string;
};

export type TableEdgeProps = Required<SmoothStepEdgeProps<ITableEdgeData>>;

export type TablesRanking = (params: ITablesRankingParams) => Node<ITableNodeData>[];

export type OnSliderValueChangeParams = number | number[];

export type ConnectionSourceTempNodeData = {
  tableId: string;
  sourceHandleId: string;
  fieldId: number;
};

export type ConnectionSourceTempNode = ConnectionSourceTempNodeData | null;

type GetHandleValueParams = {
  handlePosition: 'top' | 'bottom';
  clientX: number;
  left: number;
  right: number;
};

export type GetHandleValue = (params: GetHandleValueParams) => string;

export type ParseErrorLocationDirection = {
  column: number;
  line: number;
  offset: number;
};

export type ParseErrorExpectedLiteral = {
  type: 'literal';
  ignoreCase: boolean;
  text: string;
};

export type ParseErrorExpectedOther = {
  type: 'other';
  description: string;
};

type HandleType = 'source' | 'target';

export type GetHandleDirection = (
  type: HandleType,
) => typeof HANDLE_LEFT_ID_PART | typeof HANDLE_RIGHT_ID_PART;

export type GetHandleOrientation = (
  relationLabel: typeof SINGLE_CONNECTION_LABEL | typeof MULTI_CONNECTION_LABEL,
) => typeof HANDLE_ID_TOP_SIDE_PART | typeof HANDLE_ID_BOTTOM_SIDE_PART;

export type SendButtonIds = [string] | [string, string];

export type DragEventType = MouseEvent<Element, globalThis.MouseEvent>;

export type EdgeLabelProps = {
  transform: string;
  label: string;
};

export type TablesWidgetReducerStateType = {
  shouldTablesFromTargetInputRender: boolean;
  isEnabledTablesCommonRender: boolean;
  shouldTablesSecondRender: boolean;
  isShouldFitView: boolean;
  updateButtonEventListener: string;
  shouldRerenderForSaveButton: string;
};

export type SetShouldTablesFromTargetInputRenderActionType = {
  type: 'setShouldTablesFromTargetInputRender';
  payload: TablesWidgetReducerStateType['shouldTablesSecondRender'];
};
export type SetIsEnabledTablesCommonRenderActionType = {
  type: 'setIsEnabledTablesCommonRender';
  payload: TablesWidgetReducerStateType['isEnabledTablesCommonRender'];
};
export type SetShouldTablesSecondRenderActionType = {
  type: 'setShouldTablesSecondRender';
  payload: TablesWidgetReducerStateType['shouldTablesSecondRender'];
};
export type SetIsShouldFitViewActionType = {
  type: 'setIsShouldFitView';
  payload: TablesWidgetReducerStateType['isShouldFitView'];
};
export type SetUpdateButtonEventListenerActionType = {
  type: 'setUpdateButtonEventListener';
  payload: TablesWidgetReducerStateType['updateButtonEventListener'];
};
export type SetShouldRerenderForSaveButtonActionType = {
  type: 'setShouldRerenderForSaveButton';
  payload: TablesWidgetReducerStateType['shouldRerenderForSaveButton'];
};

export type TablesWidgetReducerActionsType =
  | SetShouldTablesFromTargetInputRenderActionType
  | SetIsEnabledTablesCommonRenderActionType
  | SetShouldTablesSecondRenderActionType
  | SetIsShouldFitViewActionType
  | SetUpdateButtonEventListenerActionType
  | SetShouldRerenderForSaveButtonActionType;
