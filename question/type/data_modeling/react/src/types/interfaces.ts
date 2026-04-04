import { Dispatch as ReactDispatch } from 'react';

import Database from '@dbml/core/types/model_structure/database';
import { SmoothStepPathOptions } from '@reactflow/core/dist/esm/types/edges';
import { Dispatch as RTKDispatch } from '@reduxjs/toolkit';
import { Edge, NodeProps, Position } from 'reactflow';

import {
  ConnectionSourceTempNode,
  EdgeForServerType,
  EdgeMarkersOptions,
  EdgeType,
  FieldType,
  NodeForServerType,
  NodeType,
  ParseErrorExpectedLiteral,
  ParseErrorExpectedOther,
  ParseErrorLocationDirection,
  SendButtonIds,
  TablesWidgetReducerActionsType,
} from './types';

export interface ITableNodeData {
  tableName: string;
  fields: FieldType[];
  alias: string | null;
  tableWidth: number;
  tableHeight: number;
  isTouched: boolean;
}

export interface ParseError {
  expected: Array<ParseErrorExpectedOther | ParseErrorExpectedLiteral>;
  found: string;
  location: {
    end: ParseErrorLocationDirection;
    start: ParseErrorLocationDirection;
  };
  message: string;
  name: string;
}

export interface EditorInputEvent extends CustomEvent {
  readonly type: 'editor_input';
  readonly data: string;
}

export interface IErrorEventData {
  column: number;
  row: number;
  text: string;
  type: 'error' | 'warning' | 'information';
}

export interface INewEditorTextEventData {
  text: string;
}

export interface ITablesWidgetProps {
  sendButtonIds: SendButtonIds;
  editorTextareaElement: HTMLTextAreaElement;
  containerClassName?: string;
}

export interface IButtonEventListenerUpdaterProps {
  buttonIds: SendButtonIds;
  nodes: NodeType[];
  edges: EdgeType[];
  editorText: string;
}

export interface ISetChangesOnInputParams {
  nodes: NodeType[];
  edges: EdgeType[];
  editorText: string;
}

export interface IInitialStateFromInput {
  tables: NodeType[];
  refs: EdgeType[];
  editorText: string;
}

export interface IStageState {
  nodes: NodeType[];
  edges: EdgeType[];
  connectionSourceTempNode: ConnectionSourceTempNode;
  newEdge: Edge | null;
  editorText: string;
  errorMessage: string;
  sourceTableId: string;
  isConnectionComplete: boolean;
  isCursorLeave: boolean;
  isNeedToRemoveTempLine: boolean;
  isTableDragging: boolean;
}

export interface IDataForServer {
  tables: NodeForServerType[];
  refs: EdgeForServerType[];
}

export interface ITablesParserParams {
  editorText: IStageState['editorText'];
  parserResult: Database;
  nodes: IStageState['nodes'];
}

export interface ITablesRankingParams {
  tables: NodeType[];
}

export interface IEdgesParserParams {
  editorText: IStageState['editorText'];
  parserResult: Database;
  nodes: IStageState['nodes'];
  edges: IStageState['edges'];
}

export interface ITableNodeProps extends NodeProps {
  data: ITableNodeData;
}

export interface ITablesColumn {
  tables: NodeType[];
  width: number;
  index: number;
}

export interface IFieldHandleProps {
  tableId: string;
  style: {
    [key: string]: number | string;
  };
  id: string;
  position: Position.Left | Position.Right;
}

export interface ITableEdgeData {
  startEdgeLabel: string;
  endEdgeLabel: string;
  pathOptions: Required<SmoothStepPathOptions>;
  markerOptions: EdgeMarkersOptions;
  sourceFieldId: number; // for server
  targetFieldId: number; // for server
}

export interface IUseTableSizeReturnData {
  maxLeft: number;
  maxRight: number;
  headerWidth: number;
  initCoordsForHandle: number[];
}

export interface IField {
  id: number;
  leftText: string;
  rightText: string;
  isPrimaryKey: boolean;
}

export interface ITableFieldProps {
  field: IField;
  sizes: IUseTableSizeReturnData;
  tableId: string;
  fieldIndex: number;
}

export interface IAnalyzeEditorTextParams {
  event: Event;
  dispatch: RTKDispatch;
  reducerDispatch: ReactDispatch<TablesWidgetReducerActionsType>;
  sendButtonIds: SendButtonIds;
}

export interface ICatchEditorTextErrorParams {
  correctEditorText: string;
  sendButtonIds: SendButtonIds;
  error: ParseError;
  dispatch: RTKDispatch;
}
