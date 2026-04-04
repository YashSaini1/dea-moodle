import {
  EdgeType,
  NodeType,
  TablesWidgetReducerActionsType,
  TablesWidgetReducerStateType,
} from '@projectTypes/types';
import { EdgeTypes } from '@reactflow/core/dist/esm/types';
import { NodeTypes } from '@reactflow/core/dist/esm/types/general';
import { TableEdge } from '@widgets/tablesWidget/entities/tableEdge/TableEdge';
import { TableNode } from '@widgets/tablesWidget/entities/tableNode/TableNode';
import { nanoid } from 'nanoid';

export const emptyNodes: NodeType[] = [];

export const emptyEdges: EdgeType[] = [];

export const nodeTypes: NodeTypes = {
  sqlTable: TableNode,
};

export const edgeTypes: EdgeTypes = {
  default: TableEdge,
};

export const reducerAction = (
  state: TablesWidgetReducerStateType,
  action: TablesWidgetReducerActionsType,
) => {
  switch (action.type) {
    case 'setShouldTablesFromTargetInputRender':
      return { ...state, shouldTablesFromTargetInputRender: action.payload };
    case 'setIsEnabledTablesCommonRender':
      return { ...state, isEnabledTablesCommonRender: action.payload };
    case 'setShouldTablesSecondRender':
      return { ...state, shouldTablesSecondRender: action.payload };
    case 'setIsShouldFitView':
      return { ...state, isShouldFitView: action.payload };
    case 'setUpdateButtonEventListener':
      return { ...state, updateButtonEventListener: action.payload };
    case 'setShouldRerenderForSaveButton':
      return { ...state, shouldRerenderForSaveButton: action.payload };
    default:
      return state;
  }
};

export const reducerInitialState: TablesWidgetReducerStateType = {
  shouldTablesFromTargetInputRender: false,
  isEnabledTablesCommonRender: false,
  shouldTablesSecondRender: false,
  isShouldFitView: false,
  updateButtonEventListener: '',
  shouldRerenderForSaveButton: nanoid(),
};
