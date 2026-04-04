import { RootState } from '../rootReducer';

export const getSourceTableId = (state: RootState) => state.stage.sourceTableId;
export const getEditorText = (state: RootState) => state.stage.editorText;
export const getErrorMessage = (state: RootState) => state.stage.errorMessage;
export const getConnectionSourceTempNode = (state: RootState) =>
  state.stage.connectionSourceTempNode;
export const getNodes = (state: RootState) => state.stage.nodes;
export const getEdges = (state: RootState) => state.stage.edges;
export const getNewEdge = (state: RootState) => state.stage.newEdge;
export const getIsConnectionComplete = (state: RootState) =>
  state.stage.isConnectionComplete;
export const getIsNeedToRemoveTempLine = (state: RootState) =>
  state.stage.isNeedToRemoveTempLine;
export const getIsCursorLeave = (state: RootState) => state.stage.isCursorLeave;
export const getIsTableDragging = (state: RootState) => state.stage.isTableDragging;
